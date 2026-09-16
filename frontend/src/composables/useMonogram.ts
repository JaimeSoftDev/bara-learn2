const PALETTE = [
  { bg: '#dbe6f2', fg: '#2c4f76' }, // pastel blue / navy
  { bg: '#dcead9', fg: '#2f6a45' }, // pastel green / forest
  { bg: '#e9e1f2', fg: '#5b4a86' }, // pastel lavender / indigo
  { bg: '#f2e6d8', fg: '#8a5a2b' }, // pastel peach / brown
  { bg: '#e2f0ee', fg: '#2c6e66' }, // pastel teal / deep teal
]

const STOPWORDS = new Set(['de', 'del', 'la', 'el', 'los', 'las', 'y', 'en', 'con', 'para', 'por', 'a'])

/** Two-letter initials from a course/category title, e.g. "Genética de Poblaciones" -> "GP". */
export function initialsFor(title: string): string {
  const words = title
    .trim()
    .split(/\s+/)
    .filter((w) => w && !STOPWORDS.has(w.toLowerCase()))
  const letters = words.slice(0, 2).map((w) => w.charAt(0).toUpperCase())
  return letters.join('') || '·'
}

/** Deterministic pastel/ink color pair for a given string (stable across renders). */
export function paletteFor(seed: string): { bg: string; fg: string } {
  let hash = 0
  for (let i = 0; i < seed.length; i++) {
    hash = (hash * 31 + seed.charCodeAt(i)) >>> 0
  }
  return PALETTE[hash % PALETTE.length]!
}

/** Evenly cycles the palette by a numeric id, for nicer distribution across a list. */
export function paletteForId(id: number): { bg: string; fg: string } {
  return PALETTE[id % PALETTE.length]!
}
