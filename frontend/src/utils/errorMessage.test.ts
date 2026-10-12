import { describe, expect, it } from 'vitest'
import { ApiError, NetworkError } from '../api'
import { errorMessage } from './errorMessage'

describe('errorMessage', () => {
  it('asks to check the connection when the API is unreachable', () => {
    expect(errorMessage(new NetworkError(new TypeError('Failed to fetch')))).toContain('Connexion impossible')
  })

  it('reports a slow server when the request timed out', () => {
    expect(errorMessage(new DOMException('Timed out', 'TimeoutError'))).toContain('trop de temps')
  })

  it('asks to wait when the request was rate limited', () => {
    expect(errorMessage(new ApiError(429))).toContain('patientez')
  })

  it('falls back to a server problem for any other failure', () => {
    expect(errorMessage(new ApiError(500))).toBe('Le serveur a rencontré un problème.')
    expect(errorMessage(new SyntaxError('Unexpected token <'))).toBe('Le serveur a rencontré un problème.')
  })
})
