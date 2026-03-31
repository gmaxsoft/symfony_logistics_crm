// Cypress E2E support file - runs before each spec file

// Import custom commands
import './commands'

// Suppress uncaught exceptions from app (e.g. from network errors in tests)
Cypress.on('uncaught:exception', (err) => {
  // Ignore Leaflet and network errors that don't break the test
  if (
    err.message.includes('ResizeObserver') ||
    err.message.includes('NetworkError') ||
    err.message.includes('Cannot read properties of null')
  ) {
    return false
  }
  return true
})
