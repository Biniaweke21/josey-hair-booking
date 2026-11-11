# Beauty by Josey: booking system

Website and booking system for Josey, a hair braiding stylist in Maryland, USA.

Customers pick a style, choose its length and size and any add-ons, and only see time slots that actually fit. Availability comes from the stylist's business hours, days off and weekly schedule, plus how long that exact combination of options takes. Josey runs everything from an admin dashboard.

**What's in it**

- Public site: home, services, gallery, about, contact and booking flow
- Services with variation types (length, size…), per-option pricing and duration, and add-ons
- Availability engine: business hours, one-off exceptions, staff weekly schedules and leave, capacity
- Admin: bookings, customers, employees, gallery, notification templates and logs, reports, settings
- Roles and permissions (spatie/laravel-permission)

**Stack:** Vue 3, Pinia, Vue Router and Vite on the front (deployed on Vercel); Laravel 12, Sanctum and MySQL on the back.

## Run it locally

```bash
# API
cd server
cp .env.example .env        # set DB and ADMIN_EMAIL / ADMIN_PASSWORD
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve           # http://localhost:8000

# Website
cd ../client
npm install
echo "VITE_API_BASE_URL=http://localhost:8000/api" > .env
npm run dev                 # http://localhost:5173
```
