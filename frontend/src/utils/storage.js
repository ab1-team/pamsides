const API_BASE = import.meta.env.VITE_API_BASE_URL || 'http://localhost:8000/api'

export const storageUrl = (path) => {
  if (!path) return ''
  if (/^https?:\/\//i.test(path)) return path
  const origin = API_BASE.replace(/\/api\/?$/, '')
  const clean = String(path).replace(/^\/+/, '')
  return `${origin}/${clean}`
}
