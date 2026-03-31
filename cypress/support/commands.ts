/// <reference types="cypress" />

// Custom Cypress commands for Logistics CRM

/**
 * Create a parcel via API and return its data
 */
Cypress.Commands.add('createParcel', (overrides = {}) => {
  const defaults = {
    senderAddress: 'ul. Testowa 1, 00-001 Warszawa',
    receiverAddress: 'ul. Odbiorcza 5, 31-001 Kraków',
    weight: 2.5,
    courierName: 'Test Courier',
    senderLatitude: 52.2297,
    senderLongitude: 21.0122,
    receiverLatitude: 50.0647,
    receiverLongitude: 19.9450,
  }

  return cy
    .request({
      method: 'POST',
      url: `${Cypress.env('apiUrl')}/parcels`,
      body: { ...defaults, ...overrides },
      headers: { 'Content-Type': 'application/json' },
    })
    .its('body')
})

/**
 * Apply a workflow transition to a parcel via API
 */
Cypress.Commands.add('applyTransition', (parcelId: string, transition: string) => {
  return cy
    .request({
      method: 'PATCH',
      url: `${Cypress.env('apiUrl')}/parcels/${parcelId}/transition/${transition}`,
    })
    .its('body')
})

/**
 * Reset database to a clean state (requires special test endpoint)
 */
Cypress.Commands.add('resetDatabase', () => {
  cy.request({
    method: 'POST',
    url: `${Cypress.env('apiUrl')}/test/reset`,
    failOnStatusCode: false,
  })
})

// TypeScript declaration merging
declare global {
  namespace Cypress {
    interface Chainable {
      createParcel(overrides?: Record<string, unknown>): Chainable<Record<string, unknown>>
      applyTransition(parcelId: string, transition: string): Chainable<Record<string, unknown>>
      resetDatabase(): Chainable<void>
    }
  }
}
