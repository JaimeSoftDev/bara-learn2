import { isAxiosError } from 'axios'

export function apiErrorMessage(error: unknown, fallback = 'Ha ocurrido un error inesperado.'): string {
  if (isAxiosError(error)) {
    const data = error.response?.data
    if (data?.message) return data.message
    if (data?.errors) {
      const first = Object.values(data.errors)[0]
      if (Array.isArray(first) && first.length) return first[0] as string
    }
  }
  return fallback
}
