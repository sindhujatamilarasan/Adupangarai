import { fmtQty, type GroceryItem, type GroceryResponse } from '../api'
import { translate, unitLabel, type Lang } from '../i18n'
import { LOGO_SVG } from '../../../shared/logo'
import { pulliDots, sikkuLoopPaths } from '../../../shared/kolam'

/** "1 × ½ kg" when bought in shop packs, else the plain quantity ("" if none). */
export const toBuy = (lang: Lang, i: GroceryItem) =>
  i.packs && i.ingredient?.pack_label ? `${i.packs} × ${translate(lang, i.ingredient.pack_label)}` : i.quantity ? `${fmtQty(i.quantity)} ${unitLabel(lang, i.unit)}` : ''

/** What's still to buy, in shop-section order (the server sends it sorted). */
export function sectionsToBuy(list: GroceryResponse): { name: string; icon: string; items: GroceryItem[] }[] {
  const out: { name: string; icon: string; items: GroceryItem[] }[] = []
  for (const i of list.data.filter((x) => !x.purchased)) {
    const last = out.at(-1)
    if (last?.name === i.section) last.items.push(i)
    else out.push({ name: i.section, icon: i.section_icon, items: [i] })
  }
  return out
}

/** The list as a WhatsApp message (*bold* headings). */
export function groceryText(lang: Lang, list: GroceryResponse, kitchen: string): string {
  const t = (k: string, v?: Record<string, string | number>) => translate(lang, k, v)
  const lines = [`🛒 *${t('Grocery list')}* — ${kitchen}`]
  for (const s of sectionsToBuy(list)) {
    lines.push('', `${s.icon} *${s.name}*`)
    for (const i of s.items) lines.push(`• ${i.label}${toBuy(lang, i) ? ` — ${toBuy(lang, i)}` : ''}`)
  }
  if (list.summary.estimated_cost) lines.push('', t('≈ ₹{n} to buy', { n: list.summary.estimated_cost.toLocaleString('en-IN') }))
  lines.push('', `— ${t('Made with Adupangarai')}`)
  return lines.join('\n')
}

// Bright festival colours for the section headings, cycled.
const COLOURS = ['#e4572e', '#2e86ab', '#4f8a3c', '#a23b72', '#d98e04', '#7a4320']
const W = 1080
const PAD = 64
const SANS = '"Plus Jakarta Sans", "Noto Sans Tamil", sans-serif'
const SERIF = '"Fraunces", "Noto Serif Tamil", Georgia, serif'

function loadImage(src: string): Promise<HTMLImageElement> {
  return new Promise((ok, fail) => {
    const img = new Image()
    img.onload = () => ok(img)
    img.onerror = fail
    img.src = src
  })
}

/** Cut text to fit maxWidth with an ellipsis. */
function fit(ctx: CanvasRenderingContext2D, text: string, maxWidth: number): string {
  if (ctx.measureText(text).width <= maxWidth) return text
  let s = text
  while (s.length > 1 && ctx.measureText(s + '…').width > maxWidth) s = s.slice(0, -1)
  return s + '…'
}

/** A sikku kolam drawn at (x, y), `size` px across, in the given line and dot colours. */
function kolam(ctx: CanvasRenderingContext2D, x: number, y: number, size: number, line: string, dot: string) {
  const m = 4
  const scale = size / (m + 1.6)
  ctx.save()
  ctx.translate(x + size / 2, y + size / 2)
  ctx.rotate(Math.PI / 4)
  ctx.scale(scale, scale)
  ctx.translate(-m / 2, -m / 2)
  ctx.strokeStyle = line
  ctx.lineWidth = 0.08
  ctx.lineCap = 'round'
  for (const d of sikkuLoopPaths(m, m)) ctx.stroke(new Path2D(d))
  ctx.fillStyle = dot
  for (const [cx, cy] of pulliDots(m, m)) {
    ctx.beginPath()
    ctx.arc(cx, cy, 0.1, 0, Math.PI * 2)
    ctx.fill()
  }
  ctx.restore()
}

/** The shopping list as a bright PNG for WhatsApp: kolam header, sections, tick boxes, estimated cost. */
export async function groceryImage(lang: Lang, list: GroceryResponse, kitchen: string, dates: string): Promise<Blob> {
  const t = (k: string, v?: Record<string, string | number>) => translate(lang, k, v)
  await Promise.all([`600 40px ${SANS}`, `500 32px ${SANS}`, `600 60px ${SERIF}`].map((f) => document.fonts.load(f, 'அa').catch(() => [])))
  const logo = await loadImage(`data:image/svg+xml;charset=utf-8,${encodeURIComponent(LOGO_SVG)}`).catch(() => null)

  const sections = sectionsToBuy(list)
  const ROW = 68
  const HEAD = 300
  const height = HEAD + 40 + sections.reduce((h, s) => h + 90 + s.items.length * ROW + 24, 0) + 200

  const canvas = document.createElement('canvas')
  canvas.width = W
  canvas.height = height
  const ctx = canvas.getContext('2d')!
  ctx.textBaseline = 'middle'

  // Page
  ctx.fillStyle = '#fff8ec'
  ctx.fillRect(0, 0, W, height)

  // Header band: warm gradient with a big kolam
  const g = ctx.createLinearGradient(0, 0, W, HEAD)
  g.addColorStop(0, '#c2410c')
  g.addColorStop(0.55, '#e4572e')
  g.addColorStop(1, '#f2b705')
  ctx.fillStyle = g
  ctx.fillRect(0, 0, W, HEAD)
  kolam(ctx, W - 250, -30, 330, 'rgba(255,250,243,.45)', '#fff3b0')
  if (logo) ctx.drawImage(logo, PAD, 58, 110, 110)
  ctx.fillStyle = '#fffaf3'
  ctx.font = `600 60px ${SERIF}`
  ctx.fillText(fit(ctx, `🛒 ${t('Grocery list')}`, W - PAD * 2 - 140 - 130), PAD + 140, 96)
  ctx.font = `600 34px ${SANS}`
  ctx.fillText(fit(ctx, kitchen, W - PAD * 2 - 140 - 200), PAD + 140, 156)
  ctx.font = `500 30px ${SANS}`
  ctx.fillStyle = 'rgba(255,250,243,.9)'
  ctx.fillText(t('{n} items', { n: sections.reduce((n, s) => n + s.items.length, 0) }) + (dates ? ` · ${dates}` : ''), PAD, 236)

  // Sections
  let y = HEAD + 40
  sections.forEach((s, si) => {
    const colour = COLOURS[si % COLOURS.length]
    ctx.font = `600 36px ${SANS}`
    const label = `${s.icon}  ${s.name}`
    const pill = ctx.measureText(label).width + 56
    ctx.fillStyle = colour
    ctx.beginPath()
    ctx.roundRect(PAD, y, pill, 66, 33)
    ctx.fill()
    ctx.fillStyle = '#fff'
    ctx.fillText(label, PAD + 28, y + 34)
    y += 90

    for (const i of s.items) {
      // tick box
      ctx.strokeStyle = colour
      ctx.lineWidth = 3
      ctx.beginPath()
      ctx.roundRect(PAD + 8, y + 14, 36, 36, 8)
      ctx.stroke()
      // quantity on the right
      ctx.font = `500 32px ${SANS}`
      ctx.fillStyle = '#76675c'
      const q = toBuy(lang, i)
      const qw = ctx.measureText(q).width
      ctx.fillText(q, W - PAD - qw, y + 33)
      // name
      ctx.font = `600 36px ${SANS}`
      ctx.fillStyle = '#231a14'
      ctx.fillText(fit(ctx, `${i.ingredient?.display_icon ?? '🧴'}  ${i.label}`, W - PAD * 2 - 72 - qw - 24), PAD + 72, y + 33)
      // dotted rule
      ctx.strokeStyle = '#ece4d8'
      ctx.lineWidth = 2
      ctx.setLineDash([4, 8])
      ctx.beginPath()
      ctx.moveTo(PAD + 72, y + ROW - 2)
      ctx.lineTo(W - PAD, y + ROW - 2)
      ctx.stroke()
      ctx.setLineDash([])
      y += ROW
    }
    y += 24
  })

  // Footer: cost and app name between kolam dots
  y += 30
  if (list.summary.estimated_cost) {
    ctx.font = `600 40px ${SANS}`
    ctx.fillStyle = '#7a4320'
    ctx.fillText(`💰 ${t('≈ ₹{n} to buy', { n: list.summary.estimated_cost.toLocaleString('en-IN') })}`, PAD, y)
  }
  ctx.font = `500 26px ${SANS}`
  ctx.fillStyle = '#76675c'
  ctx.fillText(t('Made with Adupangarai'), PAD, y + 70)
  const dots = ['#e4572e', '#f2b705', '#4f8a3c', '#2e86ab', '#a23b72']
  for (let k = 0; k < 9; k++) {
    ctx.fillStyle = dots[k % dots.length]
    ctx.beginPath()
    ctx.arc(W - PAD - k * 30, y + 70, 7, 0, Math.PI * 2)
    ctx.fill()
  }

  return new Promise((ok, fail) => canvas.toBlob((b) => (b ? ok(b) : fail(new Error('Could not make the image'))), 'image/png'))
}

/** Share sheet (phone: WhatsApp etc.) when the browser can share files, else a download. */
export async function shareOrDownload(blob: Blob, filename: string, title: string): Promise<'shared' | 'downloaded' | 'cancelled'> {
  const file = new File([blob], filename, { type: blob.type })
  if (navigator.canShare?.({ files: [file] })) {
    try {
      await navigator.share({ files: [file], title })
      return 'shared'
    } catch (e) {
      if ((e as DOMException).name === 'AbortError') return 'cancelled'
    }
  }
  const url = URL.createObjectURL(blob)
  const a = Object.assign(document.createElement('a'), { href: url, download: filename })
  a.click()
  setTimeout(() => URL.revokeObjectURL(url), 1000)
  return 'downloaded'
}
