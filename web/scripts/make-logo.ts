// Regenerates the brand mark from the kolam generator:  node --experimental-strip-types scripts/make-logo.ts
import { writeFileSync } from 'node:fs'
import { pulliDots, sikkuPath } from '../src/lib/kolam.ts'

const [m, n] = [2, 3] // 2 x 3 pulli: one unbroken line, four petals
const scale = (64 / (Math.max(m, n) + 1.9)) * 0.78
const body = `<g transform="translate(32 32) scale(${scale.toFixed(4)}) translate(${-m / 2} ${-n / 2})">`
  + `<path d="${sikkuPath(m, n)}" fill="none" stroke="#fffaf3" stroke-width=".1" stroke-linecap="round" stroke-linejoin="round"/>`
  + `<g fill="#f0c27a">${pulliDots(m, n).map(([x, y]) => `<circle cx="${x}" cy="${y}" r=".1"/>`).join('')}</g></g>`
const logo = `<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" role="img" aria-label="Adupangarai">`
  + `<defs><linearGradient id="t" x1="0" y1="0" x2="1" y2="1"><stop offset="0" stop-color="#8a4c26"/><stop offset="1" stop-color="#5c3118"/></linearGradient></defs>`
  + `<rect width="64" height="64" rx="15" fill="url(#t)"/>${body}</svg>\n`

writeFileSync(new URL('../public/logo.svg', import.meta.url), logo)
writeFileSync(new URL('../public/favicon.svg', import.meta.url), logo)
console.log('logo.svg + favicon.svg written')
