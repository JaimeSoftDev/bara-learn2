# Bara Learn

Plataforma de cursos online estilo Udemy/Teachable: los profesores crean cursos organizados en
secciones y lecciones, donde cada lección combina un **vídeo de YouTube** (pegando el enlace) con
**contenido escrito**. Los alumnos se inscriben (gratis, pagando con Stripe, o porque el profesor
les da acceso manualmente tras un pago en efectivo), avanzan lección a lección, hacen preguntas,
dejan valoraciones y obtienen un certificado en PDF al completar el curso.

## Stack

- **Backend**: Laravel 13 (API REST) + Sanctum (auth SPA por cookies) + MySQL
- **Frontend**: Vue 3 (Composition API) + Vite + TypeScript + Pinia + Vue Router + Tailwind CSS v4
- **Pagos**: Stripe Checkout (cursos de pago online) + inscripción manual "en efectivo" por el profesor
- **Editor de contenido**: Tiptap (WYSIWYG) para el texto que acompaña a cada vídeo
- **Certificados**: generación de PDF con DomPDF, verificables por código público

## Funcionalidades incluidas

- Roles: **alumno**, **profesor**, **administrador**
- Catálogo público con búsqueda, filtros (categoría, nivel, precio) y ordenación
- Página de curso con currículo, vista previa gratuita de lecciones, valoraciones y perfil del instructor
- Editor de curso para profesores: detalles, secciones y lecciones (URL de YouTube + editor enriquecido), publicar/despublicar
- Reproductor de YouTube con detección de fin de vídeo para marcar la lección como completada automáticamente
- Preguntas y respuestas por lección
- Inscripción gratuita, pago con Stripe, o **acceso manual concedido por el profesor cuando el alumno paga en efectivo** (queda registrado como pedido y se ve igual que una compra online)
- Progreso por curso, certificados en PDF y verificación pública de certificados
- Favoritos (wishlist)
- Panel de alumno (mis cursos, favoritos, certificados), panel de profesor (cursos, alumnos, ingresos Stripe vs. efectivo) y panel de administración (usuarios, cursos, categorías)

## Estructura del repositorio

```
backend/    Laravel (API REST)
frontend/   Vue 3 + Vite (SPA)
docker-compose.yml   MySQL para desarrollo local
```

## Puesta en marcha

### 1. Base de datos (MySQL vía Docker)

```bash
docker compose up -d
```

Esto levanta MySQL en `localhost:3306` con la base de datos `bara_learn` (usuario `bara_learn` /
contraseña `secret`), tal como espera `backend/.env.example`.

> Alternativa sin Docker: en `backend/.env` cambia `DB_CONNECTION=sqlite` y ejecuta
> `touch database/database.sqlite` dentro de `backend/`.

### 2. Backend (Laravel)

```bash
cd backend
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

El backend queda disponible en `http://localhost:8000`. El seeder crea categorías, dos profesores,
varios alumnos, cursos de ejemplo y, entre otras cosas, un alumno con acceso concedido en efectivo,
para poder ver esa funcionalidad en acción de inmediato.

Usuarios de demostración (contraseña `password` para todos):

| Rol       | Email                     |
|-----------|---------------------------|
| Admin     | admin@baralearn.test      |
| Profesor  | laura@baralearn.test      |
| Profesor  | marcos@baralearn.test     |
| Alumno    | ana@baralearn.test        |
| Alumno    | pedro@baralearn.test      |
| Alumno    | sofia@baralearn.test      |

### 3. Frontend (Vue)

```bash
cd frontend
npm install
cp .env.example .env
npm run dev
```

La SPA queda disponible en `http://localhost:5173`.

> Backend y frontend deben servirse ambos bajo `localhost` (no mezclar `localhost` con `127.0.0.1`),
> ya que la autenticación de Sanctum usa cookies con `SameSite`, que tratan hosts distintos como
> sitios distintos.

### 4. Configurar Stripe (opcional, para cursos de pago online)

En `backend/.env` añade tus claves de test de Stripe:

```
STRIPE_KEY=pk_test_...
STRIPE_SECRET=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

Y apunta un webhook de Stripe (evento `checkout.session.completed`) a
`POST /api/stripe/webhook`. En local puedes usar `stripe listen --forward-to localhost:8000/api/stripe/webhook`.

Si no configuras Stripe, los cursos gratuitos y el acceso manual en efectivo funcionan igualmente
sin ninguna configuración adicional.

## Cobro en efectivo / fuera de la plataforma

Desde el panel del profesor → curso → "Alumnos", el profesor puede introducir el email de un
alumno ya registrado y concederle acceso al curso de pago. Esto crea un pedido (`payment_method:
cash`) y una inscripción idéntica a una compra por Stripe: el alumno ve el curso como comprado, con
todas las lecciones desbloqueadas, en su sección "Mis cursos".

## Comandos útiles

```bash
# Backend
cd backend
php artisan test          # tests
php artisan migrate:fresh --seed   # resetear BD con datos de demo

# Frontend
cd frontend
npm run build              # build de producción (incluye type-check)
npm run lint                # lint + formato
```
