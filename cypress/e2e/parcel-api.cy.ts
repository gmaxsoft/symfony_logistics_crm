/// <reference types="cypress" />

/**
 * E2E API Tests: Parcel REST API
 * Tests the Symfony REST API directly
 */
describe('Parcel REST API', () => {
  const apiUrl = Cypress.env('apiUrl')

  it('GET /api/parcels returns 200', () => {
    cy.request('GET', `${apiUrl}/parcels`).then((response) => {
      expect(response.status).to.equal(200)
      expect(response.body).to.be.an('array')
    })
  })

  it('POST /api/parcels creates parcel with auto tracking number', () => {
    cy.request({
      method: 'POST',
      url: `${apiUrl}/parcels`,
      body: {
        senderAddress: 'ul. API Test 1, 00-001 Warszawa',
        receiverAddress: 'ul. API Test 2, 31-001 Kraków',
        weight: 1.234,
      },
    }).then((response) => {
      expect(response.status).to.equal(201)
      expect(response.body.id).to.be.a('string')
      expect(response.body.trackingNumber).to.match(/^PLG/)
      expect(response.body.status).to.equal('draft')
      // JSON float / DECIMAL serializacja — unikaj sztywnego === 1.234
      expect(response.body.weight).to.be.closeTo(1.234, 0.0001)
    })
  })

  it('POST /api/parcels validates required fields', () => {
    cy.request({
      method: 'POST',
      url: `${apiUrl}/parcels`,
      body: {},
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.be.oneOf([400, 422])
    })
  })

  it('GET /api/parcels/{id}/transitions returns available transitions', () => {
    cy.request({
      method: 'POST',
      url: `${apiUrl}/parcels`,
      body: {
        senderAddress: 'ul. Transitions Test, Warszawa',
        receiverAddress: 'ul. Receiver, Kraków',
        weight: 1.0,
      },
    }).then((createResponse) => {
      const id = createResponse.body.id

      cy.request('GET', `${apiUrl}/parcels/${id}/transitions`).then((response) => {
        expect(response.status).to.equal(200)
        expect(response.body.available_transitions).to.be.an('array')
        expect(response.body.current_status).to.equal('draft')

        const names = response.body.available_transitions.map((t: { name: string }) => t.name)
        expect(names).to.include('pick_up')
      })
    })
  })

  it('PATCH /api/parcels/{id}/transition/{name} applies transition', () => {
    cy.request({
      method: 'POST',
      url: `${apiUrl}/parcels`,
      body: {
        senderAddress: 'ul. Transition Apply, Warszawa',
        receiverAddress: 'ul. Receiver, Kraków',
        weight: 1.0,
      },
    }).then((createResponse) => {
      const id = createResponse.body.id

      cy.request('PATCH', `${apiUrl}/parcels/${id}/transition/pick_up`).then((response) => {
        expect(response.status).to.equal(200)
        expect(response.body.parcel.status).to.equal('picked_up')
        expect(response.body.message).to.include('pick_up')
      })
    })
  })

  it('GET /api/parcels?status=draft filters by status', () => {
    cy.request('GET', `${apiUrl}/parcels?status=draft`).then((response) => {
      expect(response.status).to.equal(200)
      expect(response.body).to.be.an('array')
      response.body.forEach((parcel: { status: string }) => {
        expect(parcel.status).to.equal('draft')
      })
    })
  })

  it('GET /api/parcels/{id} returns 404 for non-existent parcel', () => {
    cy.request({
      method: 'GET',
      url: `${apiUrl}/parcels/00000000-0000-0000-0000-000000000000`,
      failOnStatusCode: false,
    }).then((response) => {
      expect(response.status).to.equal(404)
    })
  })
})
