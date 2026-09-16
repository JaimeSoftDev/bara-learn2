# Desplegar ADNTrate en Hostinger (hosting compartido/Business con SSH)

## Cómo está pensado el despliegue

Para simplificar al máximo el despliegue en un hosting compartido (sin
acceso root, normalmente sin Node.js instalado), la aplicación se sirve
**desde un único dominio y un único documento raíz**:

- Laravel (`backend/public`) es el *document root* del dominio.
- El build de la SPA de Vue se copia dentro de `backend/public/assets` +
  `backend/public/spa-index.html`, y Laravel sirve esos archivos
  directamente (sin pasar por PHP) o, si la ruta no existe, devuelve la
  SPA para que Vue Router la gestione (modo *history*).
- La API vive bajo `/api/*` en el **mismo dominio**, así que no hay CORS,
  ni cookies cross-domain, ni necesidad de subdominios ni de Node.js en
  el servidor: solo PHP, Composer y MySQL.

El build de la SPA se genera **en tu máquina (o aquí, en este entorno)**
con `scripts/build-for-hostinger.sh` y se sube al repositorio ya
compilado. En el servidor solo hace falta `git pull` + `composer
install`.

## 0. Requisitos previos

En el panel de Hostinger (hPanel):

- **PHP 8.3 o superior** seleccionado para el dominio (hPanel → Sitios web
  → tu dominio → Configuración PHP).
- **Base de datos MySQL** creada (hPanel → Bases de datos → Bases de
  datos MySQL): apunta el host, nombre de BD, usuario y contraseña.
- **SSH activado** (hPanel → Avanzado → SSH Access) y las credenciales
  (host, puerto, usuario) a mano.
- El dominio (p. ej. `adntrate.com`) ya apuntando a Hostinger.

En tu máquina: `git`, y para poder ejecutar `scripts/build-for-hostinger.sh`
necesitas Node.js 22+ (solo aquí, no en el servidor).

## 1. Configurar el *document root* del dominio

Por defecto Hostinger apunta el dominio a `public_html/`. Como Laravel
necesita que el document root sea la carpeta `public/` del proyecto (no
la raíz del proyecto entero, por seguridad), hay que cambiarlo:

1. hPanel → **Sitios web** → tu dominio → **Configuración avanzada** →
   **Cambiar la carpeta raíz del documento (Document Root)**.
2. Súbelo a algo como `domains/adntrate.com/adntrate/backend/public`
   (el nombre exacto depende de dónde clones el proyecto en el paso
   siguiente).

## 2. Subir el código por SSH

Conéctate por SSH con los datos de hPanel:

```bash
ssh usuario@tu-servidor.hostinger.com -p 65002
```

Clona el repositorio dentro de tu carpeta de dominios (fuera de
`public_html`, ya que el document root apuntará a `.../adntrate/backend/public`):

```bash
cd ~/domains/adntrate.com
git clone https://github.com/JaimeSoftDev/bara-learn2.git adntrate
cd adntrate/backend
```

> Si tu hosting no tiene `git`, sube el proyecto entero por SFTP (FileZilla
> u otro cliente) a esa misma ruta.

## 3. Instalar dependencias PHP

```bash
composer install --no-dev --optimize-autoloader
```

Si `composer` no existe como comando, instálalo una vez:

```bash
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader
```

## 4. Configurar el entorno de producción

```bash
cp .env.example .env
nano .env   # o vi .env
```

Ajusta como mínimo:

```env
APP_NAME="ADNTrate"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://adntrate.com

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=el_nombre_de_tu_bd
DB_USERNAME=el_usuario_de_tu_bd
DB_PASSWORD=la_contraseña_de_tu_bd

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=null

SANCTUM_STATEFUL_DOMAINS=adntrate.com
FRONTEND_URLS=https://adntrate.com
FRONTEND_URL=https://adntrate.com

STRIPE_KEY=pk_live_o_test_...
STRIPE_SECRET=sk_live_o_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

> Como todo vive en el mismo dominio, `SANCTUM_STATEFUL_DOMAINS` y
> `FRONTEND_URLS` solo necesitan tu dominio sin subdominio de API aparte.

Genera la clave de la aplicación:

```bash
php artisan key:generate --force
```

## 5. Migraciones

```bash
php artisan migrate --force
```

¿Quieres los datos de demostración (cursos, profesores, alumnos de
ejemplo) también en producción? Añade `--seed`:

```bash
php artisan migrate --force --seed
```

Si no quieres datos de demo, crea tu propio administrador con Tinker:

```bash
php artisan tinker
>>> \App\Models\User::create(['name' => 'Tu Nombre', 'email' => 'tu@email.com', 'password' => bcrypt('una-contraseña-segura'), 'role' => 'admin']);
```

## 6. Permisos

```bash
chmod -R 775 storage bootstrap/cache
```

## 7. SSL

hPanel → **SSL** → activa el SSL gratuito (Let's Encrypt) para el
dominio. Suele tardar unos minutos en emitirse.

## 8. Comprobar que todo funciona

Visita `https://adntrate.com` — deberías ver la página de inicio de
ADNTrate. Prueba:

- Navegar a `/courses` y a un curso concreto (enlaces "profundos" de
  Vue Router) — deben cargar bien al refrescar la página, no dar 404.
- Registrarte / iniciar sesión.
- `https://adntrate.com/api/courses` debe devolver JSON.

## 9. Stripe (opcional, para cursos de pago online)

En el dashboard de Stripe, añade un webhook apuntando a:

```
https://adntrate.com/api/stripe/webhook
```

evento `checkout.session.completed`, y copia el "Signing secret" a
`STRIPE_WEBHOOK_SECRET` en tu `.env`.

## Cómo desplegar futuras actualizaciones

**Cambios solo de backend** (PHP): en el servidor,

```bash
cd ~/domains/adntrate.com/adntrate
git pull origin main
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
```

**Cambios de frontend** (Vue): primero, en tu máquina (o en este
entorno), desde la raíz del proyecto:

```bash
./scripts/build-for-hostinger.sh
git add backend/public/assets backend/public/spa-index.html backend/public/favicon.ico
git commit -m "Actualizar build de la SPA"
git push origin main
```

Luego, en el servidor: `git pull origin main` (no hace falta reinstalar
nada más, ya que el build compilado ya viene en el repositorio).

## Problemas comunes

- **La web da 500 con `APP_DEBUG=false`**: revisa
  `storage/logs/laravel.log` por SSH (`tail -50 storage/logs/laravel.log`).
- **Los enlaces internos dan 404 al refrescar**: confirma que el
  document root apunta a `backend/public` y que `backend/public/.htaccess`
  existe (viene con el repositorio; Hostinger usa Apache/LiteSpeed y lo
  necesita para las reescrituras de Laravel).
- **"Class not found" o error de Composer**: vuelve a ejecutar
  `composer install --no-dev --optimize-autoloader` y confirma la
  versión de PHP (`php -v`) sea 8.3+.
- **Login/registro fallan con error de CSRF**: revisa que `APP_URL` en
  `.env` coincide exactamente con el dominio real (con `https://`) y que
  `SANCTUM_STATEFUL_DOMAINS` tiene el dominio sin `https://` ni barra
  final.
