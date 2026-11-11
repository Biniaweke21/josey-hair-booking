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

// Rotating homepage hero background (full-bleed, wide).
export const HERO_IMAGES = [
  img('photo-1637777277337-f114350fb088', 1600, 1000), // salon interior
  img('photo-1541533848490-bc8115cd6522', 1600, 1000), // stylist cutting hair
  img('photo-1472747624745-ce92d32d3c24', 1600, 1000), // braided hair
  img('photo-1605980766335-d3a41c7332a1', 1600, 1000), // blonde highlighted hair
  img('photo-1560869713-bf165a9cfac1', 1600, 1000) // long flowing hair
]

// Shared page-hero banner (Services, Gallery, Contact, Booking, admin login bg) - short and wide.
export const PAGE_HERO_IMAGE = img('photo-1637777277337-f114350fb088', 1600, 500)

// "Ready for your perfect hair?" CTA banner.
export const CTA_IMAGE = premium('premium_photo-1664537435529-3ce53ff7de20', 1600, 1000)

// Generic fallback for a service/category with no uploaded photo yet.
export const FALLBACK_SERVICE_IMAGE = premium('premium_photo-1664537435457-da6196c5f49f', 800, 650)

// Admin login page photo collage (square cells).
export const ADMIN_LOGIN_COLLAGE = [
  img('photo-1734111719430-fe4a3973f8af', 500, 500), // blow-drying hair
  img('photo-1627075932202-39e03abdeb4f', 500, 500), // salon chair
  premium('premium_photo-1664049362483-58a1f21c8811', 500, 500), // haircut in progress
  img('photo-1747398690600-ffe8ecda9df1', 500, 500), // combing long hair
  img('photo-1611590027211-b954fd027b51', 500, 500), // curly hair
  img('photo-1541533848490-bc8115cd6522', 500, 500) // stylist cutting hair
]
