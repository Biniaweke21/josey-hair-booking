// Real, commercially-licensed hair-salon photography (Unsplash), used as
// decorative imagery until the salon uploads its own photos via the admin
// Gallery/Services/Categories image upload.
//
// Every URL requests a crop sized to roughly match where it's actually
// displayed, with crop=entropy so Unsplash keeps the busiest/most
// interesting region of the photo (usually the subject's face) in frame
// instead of a blind center-crop that can slice through it.

function unsplash(host, id, w, h) {
  return `https://${host}/${id}?auto=format&fit=crop&crop=entropy&q=80&w=${w}&h=${h}`
}
const img = (id, w, h) => unsplash('images.unsplash.com', id, w, h)
const premium = (id, w, h) => unsplash('plus.unsplash.com', id, w, h)

// Braid-focused photography from Pexels (free for commercial use, no
// attribution required), used until the salon uploads its own photos.
const pexels = (id, w, h) =>
  `https://images.pexels.com/photos/${id}/pexels-photo-${id}.jpeg?auto=compress&cs=tinysrgb&fit=crop&w=${w}&h=${h}`

// Rotating homepage hero background (full-bleed, wide).
export const HERO_IMAGES = [
  pexels(18006319, 1600, 1000), // knotless braids
  pexels(19177101, 1600, 1000), // braids with highlights
  pexels(11515382, 1600, 1000), // box braids
  pexels(32426283, 1600, 1000), // stitch cornrows
  pexels(30584121, 1600, 1000) // braids with gold cuffs
]

// Shared page-hero banner (Services, Gallery, Contact, Booking, admin login bg) - short and wide.
export const PAGE_HERO_IMAGE = pexels(19177101, 1600, 500)

// "Ready for your perfect hair?" CTA banner.
export const CTA_IMAGE = pexels(35272483, 1600, 1000)

// Generic fallback for a service/category with no uploaded photo yet.
export const FALLBACK_SERVICE_IMAGE = pexels(11515382, 800, 650)

// Admin login page photo collage (square cells).
export const ADMIN_LOGIN_COLLAGE = [
  img('photo-1734111719430-fe4a3973f8af', 500, 500), // blow-drying hair
  img('photo-1627075932202-39e03abdeb4f', 500, 500), // salon chair
  premium('premium_photo-1664049362483-58a1f21c8811', 500, 500), // haircut in progress
  img('photo-1747398690600-ffe8ecda9df1', 500, 500), // combing long hair
  img('photo-1611590027211-b954fd027b51', 500, 500), // curly hair
  img('photo-1541533848490-bc8115cd6522', 500, 500) // stylist cutting hair
]
