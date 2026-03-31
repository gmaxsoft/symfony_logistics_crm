/// <reference types="cypress" />

/**
 * E2E Test: Courier Parcel Workflow
 *
 * Tests the full courier workflow:
 * 1. Courier sees a parcel list
 * 2. Selects a parcel in "draft" status
 * 3. Clicks "Odbierz od nadawcy" (pick_up transition)
 * 4. Verifies status changed to "picked_up" in the UI
 * 5. Verifies status changed in the database via API
 */
describe('Courier Parcel Workflow', () => {
  let createdParcelId: string

  before(() => {
    // Create a test parcel in "draft" state via API
    cy.request({
      method: 'POST',
      url: `${Cypress.env('apiUrl')}/parcels`,
      body: {
        senderAddress: 'ul. Testowa 1, 00-001 Warszawa',
        receiverAddress: 'ul. Odbiorcza 5, 31-001 Kraków',
        weight: 1.5,
        courierName: 'Test Courier E2E',
        senderLatitude: 52.2297,
        senderLongitude: 21.0122,
        receiverLatitude: 50.0647,
        receiverLongitude: 19.9450,
      },
      headers: { 'Content-Type': 'application/json' },
    }).then((response) => {
      expect(response.status).to.equal(201)
      createdParcelId = response.body.id
      expect(response.body.status).to.equal('draft')
    })
  })

  it('Courier can see the dashboard with parcel list', () => {
    cy.visit('/courier')
    cy.contains('Panel Kuriera').should('be.visible')
    cy.get('[data-cy="parcel-card"]').should('have.length.at.least', 1)
  })

  it('Courier selects the parcel and sees the map', () => {
    cy.visit('/courier')

    // Wait for parcels to load
    cy.get('[data-cy="parcel-card"]').first().click()

    // Map should appear
    cy.get('.parcel-map').should('be.visible')
    cy.get('.leaflet-container').should('be.visible')
  })

  it('Courier selects the draft parcel and applies pick_up transition', () => {
    cy.visit('/courier')

    // Find and click on the draft parcel
    cy.get('[data-cy="parcel-card"]')
      .contains('Wersja robocza')
      .closest('[data-cy="parcel-card"]')
      .click()

    // Verify parcel detail is shown
    cy.contains('Dostępne akcje').should('be.visible')

    // Click the "Odbierz od nadawcy" button
    cy.get('[data-cy="transition-btn-pick_up"]')
      .should('be.visible')
      .contains('Odbierz od nadawcy')
      .click()

    // Verify success message
    cy.contains('Status zmieniony na').should('be.visible')

    // Verify status chip updated to "Odebrana od nadawcy"
    cy.contains('Odebrana od nadawcy').should('be.visible')
  })

  it('Verifies status change is persisted in database via API', () => {
    // Check parcel status via API
    cy.request({
      method: 'GET',
      url: `${Cypress.env('apiUrl')}/parcels/${createdParcelId}`,
    }).then((response) => {
      expect(response.status).to.equal(200)
      expect(response.body.status).to.equal('picked_up')
      expect(response.body.statusLabel).to.equal('Odebrana od nadawcy')
    })
  })

  it('Available transitions update after status change', () => {
    cy.request({
      method: 'GET',
      url: `${Cypress.env('apiUrl')}/parcels/${createdParcelId}/transitions`,
    }).then((response) => {
      expect(response.status).to.equal(200)
      const transitions = response.body.available_transitions
      const transitionNames = transitions.map((t: { name: string }) => t.name)
      expect(transitionNames).to.include('sort')
      expect(transitionNames).to.include('mark_failed')
      expect(transitionNames).not.to.include('pick_up')
    })
  })

  it('Courier can complete full workflow: pick_up → sort → deliver_start → confirm_delivery', () => {
    // Create a fresh parcel
    cy.request({
      method: 'POST',
      url: `${Cypress.env('apiUrl')}/parcels`,
      body: {
        senderAddress: 'ul. Kompletna 1, 00-001 Warszawa',
        receiverAddress: 'ul. Zakończona 5, 31-001 Kraków',
        weight: 3.0,
        senderLatitude: 52.2297,
        senderLongitude: 21.0122,
        receiverLatitude: 50.0647,
        receiverLongitude: 19.9450,
      },
    }).then((response) => {
      const id = response.body.id

      // Apply full workflow via API
      cy.request('PATCH', `${Cypress.env('apiUrl')}/parcels/${id}/transition/pick_up`).then((r) => {
        expect(r.body.parcel.status).to.equal('picked_up')
      })

      cy.request('PATCH', `${Cypress.env('apiUrl')}/parcels/${id}/transition/sort`).then((r) => {
        expect(r.body.parcel.status).to.equal('in_sorting_center')
      })

      cy.request('PATCH', `${Cypress.env('apiUrl')}/parcels/${id}/transition/deliver_start`).then((r) => {
        expect(r.body.parcel.status).to.equal('out_for_delivery')
      })

      cy.request('PATCH', `${Cypress.env('apiUrl')}/parcels/${id}/transition/confirm_delivery`).then((r) => {
        expect(r.body.parcel.status).to.equal('delivered')
        expect(r.body.parcel.deliveredAt).to.not.be.null
      })
    })
  })

  it('Cannot apply confirm_delivery from draft status', () => {
    cy.request({
      method: 'POST',
      url: `${Cypress.env('apiUrl')}/parcels`,
      body: {
        senderAddress: 'ul. Niedozwolona 1, 00-001 Warszawa',
        receiverAddress: 'ul. Odbiorcza 5, 31-001 Kraków',
        weight: 1.0,
      },
    }).then((response) => {
      const id = response.body.id

      cy.request({
        method: 'PATCH',
        url: `${Cypress.env('apiUrl')}/parcels/${id}/transition/confirm_delivery`,
        failOnStatusCode: false,
      }).then((r) => {
        expect(r.status).to.equal(422)
        expect(r.body.error).to.include('confirm_delivery')
      })
    })
  })

  it('Status filter chips work correctly in UI', () => {
    cy.visit('/courier')

    // Click "Robocze" filter
    cy.contains('Robocze').click()

    // All visible parcel cards should have "Wersja robocza" status
    cy.get('[data-cy="parcel-card"]').each(($card) => {
      cy.wrap($card).contains('Wersja robocza').should('exist')
    })
  })

  it('New parcel form validates required fields', () => {
    cy.visit('/parcels/new')
    cy.contains('Utwórz paczkę').click()

    // Validation errors should appear
    cy.contains('To pole jest wymagane').should('be.visible')
  })

  it('Can create a new parcel via form', () => {
    cy.visit('/parcels/new')

    cy.get('input[label="Adres nadawcy"]').type('ul. Nowa 1, 00-001 Warszawa')
    cy.get('input[label="Adres odbiorcy"]').type('ul. Docelowa 5, 31-001 Kraków')
    cy.get('input[label="Waga (kg)"]').type('2.5')

    cy.contains('Utwórz paczkę').click()

    // Should redirect to parcel detail
    cy.url().should('include', '/parcels/')
  })
})
