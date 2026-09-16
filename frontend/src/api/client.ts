import axios from 'axios'

// In production the SPA is served from the same origin as the Laravel API
// (see docs/deploy-hostinger.md), so an unset VITE_API_URL resolves to
// relative paths. Local development sets it explicitly in frontend/.env.
const baseURL = import.meta.env.VITE_API_URL ?? ''

export const api = axios.create({
  baseURL: `${baseURL}/api`,
  withCredentials: true,
  withXSRFToken: true,
  headers: {
    Accept: 'application/json',
  },
})

/** Sanctum SPA auth requires fetching the CSRF cookie before any state-changing request. */
export async function ensureCsrfCookie() {
  await axios.get(`${baseURL}/sanctum/csrf-cookie`, { withCredentials: true })
}

api.interceptors.response.use(
  (response) => response,
  (error) => {
    if (error.response?.status === 401) {
      // Let route guards / calling code decide how to react to an
      // expired/missing session instead of forcing a hard redirect here.
    }
    return Promise.reject(error)
  },
)
