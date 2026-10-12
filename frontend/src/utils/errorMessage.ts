import { ApiError, NetworkError } from '../api'

export function errorMessage(error: unknown): string {
  if (error instanceof NetworkError) {
    return 'Connexion impossible : vérifiez votre accès à Internet.'
  }
  if (error instanceof DOMException && error.name === 'TimeoutError') {
    return 'Le serveur met trop de temps à répondre.'
  }
  if (error instanceof ApiError && error.status === 429) {
    return 'Trop de requêtes en peu de temps : patientez quelques secondes.'
  }

  return 'Le serveur a rencontré un problème.'
}
