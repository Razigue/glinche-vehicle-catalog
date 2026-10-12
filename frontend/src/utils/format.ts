const integerFormatter = new Intl.NumberFormat('fr-FR')

export function formatPrice(cents: number | null): string | null {
  if (cents === null) {
    return null
  }

  return new Intl.NumberFormat('fr-FR', {
    style: 'currency',
    currency: 'EUR',
    minimumFractionDigits: cents % 100 === 0 ? 0 : 2,
  }).format(cents / 100)
}

export function formatMileage(kilometers: number): string {
  return `${integerFormatter.format(kilometers)} km`
}
