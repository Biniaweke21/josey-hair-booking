// Braid-focused photography from Pexels (free for commercial use, no
// attribution required), used as decorative imagery until the salon uploads
// its own photos via the admin Gallery/Services/Categories image upload.
// Each URL requests a crop sized to roughly match where it's displayed.
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
  pexels(18006319, 500, 500), // knotless braids
  pexels(9545366, 500, 500), // bohemian braids
  pexels(11515382, 500, 500), // box braids
  pexels(32426302, 500, 500), // feed-in braids
  pexels(36297542, 500, 500), // jumbo twists
  pexels(7951589, 500, 500) // comb coil locs
]
