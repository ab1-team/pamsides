function detectApiBase() {
  if (import.meta.env.VITE_API_BASE_URL) return import.meta.env.VITE_API_BASE_URL
  if (import.meta.env.VITE_BACKEND_URL) return `${import.meta.env.VITE_BACKEND_URL.replace(/\/$/, '')}/api`
  if (typeof window !== 'undefined') {
    const { protocol, hostname } = window.location
    return `${protocol}//${hostname}/api`
  }
  return '/api'
}

export const API_BASE = detectApiBase()

export const storageUrl = (path) => {
  if (!path) return ''
  if (/^https?:\/\//i.test(path)) return path
  const origin = API_BASE.replace(/\/api\/?$/, '')
  const clean = String(path).replace(/^\/+/, '')
  return `${origin}/${clean}`
}
