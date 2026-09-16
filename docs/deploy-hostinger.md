# Desplegar ADNTrate en Hostinger (jaimesoftdev.es)

## Tu estructura de carpetas

En tu servidor, dentro de `~/`, tienes:

```
~/inventario/                     ← backend de jaimesoftdev.com — NO TOCAR
~/domains/jaimesoftdev.com/       ← NO TOCAR
~/domains/neosdalud.com/          ← NO TOCAR
~/domains/jaimesoftdev.es/        ← AQUÍ vamos a desplegar ADNTrate
    └── public_html/              ← esta es la carpeta que Hostinger sirve como web pública de jaimesoftdev.es
```

Todo lo que sigue trabaja **únicamente** dentro de
`~/domains/jaimesoftdev.es/`. No se toca nada de `inventario`, ni de
`jaimesoftdev.com`, ni de `neosdalud.com`.

## Cómo está pensado el despliegue

En vez de cambiar el "document root" en el panel (que en Hostinger no
siempre es sencillo/visible), vamos a **enlazar** `public_html` a la
carpeta `public` de Laravel con un enlace simbólico. Así:

- No tocas ninguna configuración en hPanel.
- `public_html` sigue existiendo, pero pasa a ser un enlace a
  `adntrate/backend/public`.
- La API vive bajo `/api/*` en el mismo dominio que la SPA de Vue, así
  que no hay CORS, ni cookies cross-domain, ni necesidad de Node.js en
  el servidor.

El build de la SPA de Vue ya viene compilado dentro del repositorio
(carpeta `backend/public/assets` + `backend/public/spa-index.html`), así
que en el servidor **no hace falta instalar Node.js**, solo PHP,
Composer y MySQL.

## 0. Requisitos previos (una sola vez, en hPanel)

- **PHP 8.3 o superior** para el dominio `jaimesoftdev.es`: hPanel →
  Sitios web → `jaimesoftdev.es` → Configuración PHP.
- **Base de datos MySQL** creada: hPanel → Bases de datos → Bases de
  datos MySQL → crea una nueva y apunta el host, nombre de BD, usuario y
  contraseña que te muestre.
- **SSH activado**: hPanel → Avanzado → Acceso SSH. Apunta el host, el
  puerto y tu usuario SSH.
- **SSL**: hPanel → `jaimesoftdev.es` → SSL → actívalo (gratis, tarda
  unos minutos en emitirse). Hazlo ya para que esté listo cuando
  termines el resto.

## 1. Conectarte por SSH

```bash
ssh tu-usuario@tu-servidor.hostinger.com -p 65002
```

(los datos exactos —usuario, host, puerto— están en hPanel → Avanzado →
Acceso SSH).

## 2. Clonar el proyecto

Clona el repositorio **dentro de** `~/domains/jaimesoftdev.es/`, en una
carpeta nueva llamada `adntrate` (como hermana de `public_html`, no
dentro de ella):

```bash
cd ~/domains/jaimesoftdev.es
git clone https://github.com/JaimeSoftDev/bara-learn2.git adntrate
```

Con esto tendrás:

```
~/domains/jaimesoftdev.es/
    ├── public_html/         ← lo que Hostinger sirve (todavía sin tocar)
    └── adntrate/
        ├── backend/         ← Laravel (esto es lo que importa)
        └── frontend/        ← código fuente de Vue (no se usa en el servidor, ya viene compilado)
```

## 3. Instalar dependencias PHP

```bash
cd ~/domains/jaimesoftdev.es/adntrate/backend
composer install --no-dev --optimize-autoloader
```

Si el comando `composer` no existe, instálalo una vez:

```bash
curl -sS https://getcomposer.org/installer | php
php composer.phar install --no-dev --optimize-autoloader
```

(en ese caso, usa `php composer.phar` en vez de `composer` en el resto
de la guía).

## 4. Configurar el entorno de producción

```bash
cp .env.example .env
nano .env
```

Ajusta como mínimo estos valores (con los datos reales de tu BD del
paso 0):

```env
APP_NAME="ADNTrate"
APP_ENV=production
APP_DEBUG=false
APP_URL=https://jaimesoftdev.es

DB_CONNECTION=mysql
DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=el_nombre_de_tu_bd
DB_USERNAME=el_usuario_de_tu_bd
DB_PASSWORD=la_contraseña_de_tu_bd

SESSION_DRIVER=database
SESSION_SECURE_COOKIE=true
SESSION_DOMAIN=null

SANCTUM_STATEFUL_DOMAINS=jaimesoftdev.es
FRONTEND_URLS=https://jaimesoftdev.es
FRONTEND_URL=https://jaimesoftdev.es

STRIPE_KEY=pk_live_o_test_...
STRIPE_SECRET=sk_live_o_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

Guarda (en `nano`: Ctrl+O, Enter, Ctrl+X) y genera la clave de la app:

```bash
php artisan key:generate --force
```

## 5. Migraciones

```bash
php artisan migrate --force
```

¿Quieres los cursos/profesores/alumnos de ejemplo también en
producción? Añade `--seed`:

```bash
php artisan migrate --force --seed
```

Si prefieres arrancar limpio (sin datos de demo), crea tu propio
administrador:

```bash
php artisan tinker
>>> \App\Models\User::create(['name' => 'Tu Nombre', 'email' => 'tu@email.com', 'password' => bcrypt('una-contraseña-segura'), 'role' => 'admin']);
>>> exit
```

## 6. Permisos

```bash
chmod -R 775 storage bootstrap/cache
```

## 7. Enlazar `public_html` a Laravel (el paso que sustituye al "document root")

Primero, comprueba qué hay ahora mismo dentro de `public_html` (para no
perder nada por accidente):

```bash
ls -la ~/domains/jaimesoftdev.es/public_html
```

Si solo hay archivos de ejemplo/placeholder que Hostinger pone por
defecto (o está vacía), muévela a un backup y crea el enlace:

```bash
cd ~/domains/jaimesoftdev.es
mv public_html public_html_backup_$(date +%Y%m%d)
ln -s adntrate/backend/public public_html
```

Verifica que el enlace se ha creado bien:

```bash
ls -la ~/domains/jaimesoftdev.es | grep public_html
```

Debe verse algo como `public_html -> adntrate/backend/public`.

> Si prefieres no usar enlaces simbólicos (algunos hostings los
> restringen, aunque Hostinger normalmente los permite), dímelo y te
> paso la alternativa: copiar los archivos de `backend/public/*` dentro
> de `public_html` y ajustar dos rutas en `public_html/index.php`.

## 8. Comprobar que todo funciona

Visita `https://jaimesoftdev.es` — deberías ver la página de inicio de
ADNTrate. Prueba también:

- Navegar a `/courses` y a un curso concreto, y **refrescar la página**
  en esa URL — debe seguir funcionando (no dar 404).
- Registrarte / iniciar sesión.
- `https://jaimesoftdev.es/api/courses` debe devolver JSON.

## 9. Stripe (opcional, para cursos de pago online)

En el dashboard de Stripe, añade un webhook apuntando a:

```
https://jaimesoftdev.es/api/stripe/webhook
```

evento `checkout.session.completed`, y copia el "Signing secret" a
`STRIPE_WEBHOOK_SECRET` en tu `.env`.

## Cómo desplegar futuras actualizaciones

**Cambios solo de backend** (PHP): en el servidor,

```bash
cd ~/domains/jaimesoftdev.es/adntrate
git pull origin main
cd backend
composer install --no-dev --optimize-autoloader
php artisan migrate --force
```

**Cambios de frontend** (Vue): primero, en tu máquina (o pídemelo a mí),
desde la raíz del proyecto:

```bash
./scripts/build-for-hostinger.sh
git add backend/public/assets backend/public/spa-index.html backend/public/favicon.ico
git commit -m "Actualizar build de la SPA"
git push origin main
```

Luego, en el servidor: `git pull origin main` dentro de
`~/domains/jaimesoftdev.es/adntrate` — no hace falta nada más, el build
compilado ya viene en el repositorio y `public_html` ya apunta ahí por
el enlace simbólico.

## Problemas comunes

- **La web da 500 con `APP_DEBUG=false`**: revisa el log por SSH:
  `tail -50 ~/domains/jaimesoftdev.es/adntrate/backend/storage/logs/laravel.log`.
- **Los enlaces internos dan 404 al refrescar**: confirma que
  `public_html` es el enlace simbólico del paso 7 y que
  `adntrate/backend/public/.htaccess` existe (viene con el repositorio).
- **"Class not found" o error de Composer**: vuelve a ejecutar
  `composer install --no-dev --optimize-autoloader` dentro de
  `adntrate/backend`, y confirma que `php -v` es 8.3 o superior.
- **Login/registro fallan con error de CSRF**: revisa que `APP_URL` en
  `.env` sea exactamente `https://jaimesoftdev.es` y que
  `SANCTUM_STATEFUL_DOMAINS=jaimesoftdev.es` (sin `https://` ni barra
  final).
- **El enlace simbólico no funciona / la web sigue mostrando lo
  antiguo**: dime qué ves y probamos la alternativa sin symlinks
  (copiar archivos + editar `index.php`).
