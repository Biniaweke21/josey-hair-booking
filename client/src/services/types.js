// JSDoc "typed models" mirroring the backend API Resource shapes.
// Documentation-only (no runtime code); gives editor autocomplete/checking.

/**
 * @typedef {Object} Category
 * @property {number} id
 * @property {string} name
 * @property {string} slug
 * @property {string|null} description
 * @property {string|null} image_path
 * @property {string|null} image_url
 * @property {number} sort_order
 * @property {boolean} is_active
 * @property {number} [services_count]
 */

/**
 * @typedef {Object} Service
 * @property {number} id
 * @property {number} category_id
 * @property {Category} [category]
 * @property {string} name
 * @property {string} slug
 * @property {string|null} description
 * @property {string} base_price
 * @property {number} base_duration_minutes
 * @property {boolean} is_active
 */

/**
 * @typedef {Object} VariationOption
 * @property {number} id
 * @property {number} variation_type_id
 * @property {string} name
 * @property {string} slug
 * @property {number} sort_order
 * @property {boolean} is_active
 */

/**
 * @typedef {Object} VariationType
 * @property {number} id
 * @property {string} name
 * @property {string} slug
 * @property {number} sort_order
 * @property {boolean} is_active
 * @property {VariationOption[]} [options]
 */

/**
 * @typedef {Object} ServiceVariationOption
 * @property {number} id
 * @property {number} service_id
 * @property {VariationOption} [variation_option]
 * @property {string} price_adjustment
 * @property {number} duration_adjustment_minutes
 * @property {boolean} is_active
 */

/**
 * @typedef {Object} AddOn
 * @property {number} id
 * @property {string} name
 * @property {string} slug
 * @property {string|null} description
 * @property {string} price_modifier
 * @property {number} duration_modifier_minutes
 * @property {number} sort_order
 * @property {boolean} is_active
 */

/**
 * @typedef {Object} GalleryItem
 * @property {number} id
 * @property {string|null} title
 * @property {number|null} category_id
 * @property {Category} [category]
 * @property {string} image_path
 * @property {string|null} image_url
 * @property {number} sort_order
 * @property {boolean} is_active
 */

/**
 * @typedef {Object} Employee
 * @property {number} id
 * @property {string} name
 * @property {string|null} email
 * @property {string|null} phone
 * @property {boolean} is_active
 */

/**
 * @typedef {'confirmed'|'checked_in'|'completed'|'cancelled'|'no_show'} BookingStatus
 */

/**
 * @typedef {Object} Booking
 * @property {number} id
 * @property {BookingStatus} status
 * @property {string} booking_date
 * @property {string} start_time
 * @property {string} end_time
 * @property {string} subtotal
 * @property {string} addon_total
 * @property {string} total_price
 * @property {number} total_duration_minutes
 * @property {string|null} notes
 * @property {string} source
 * @property {{id:number,name:string,email:string|null,phone:string|null}} [customer]
 * @property {Employee[]} [employees]
 * @property {BookingItem[]} [items]
 * @property {string|null} confirmed_at
 * @property {string|null} checked_in_at
 * @property {string|null} completed_at
 * @property {string|null} cancelled_at
 */

/**
 * @typedef {Object} BookingItem
 * @property {number} id
 * @property {number} service_id
 * @property {string} name
 * @property {string} price
 * @property {number} duration_minutes
 * @property {Employee|null} employee
 * @property {Array<{id:number,name:string,price:string,duration_minutes:number}>} variation_options
 * @property {Array<{id:number,name:string,price:string,duration_minutes:number}>} add_ons
 */

/**
 * @typedef {Object} AvailabilityResult
 * @property {string} booking_date
 * @property {number} total_duration_minutes
 * @property {string} total_price
 * @property {Array<{start_time:string,end_time:string}>} slots
 */

/**
 * @typedef {Object} BusinessHour
 * @property {number} id
 * @property {number} day_of_week
 * @property {string|null} day_name
 * @property {string|null} open_time
 * @property {string|null} close_time
 * @property {boolean} is_closed
 */

export {}
