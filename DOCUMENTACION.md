# RETOMAP — Documentacion Completa del Backend

## Indice
1. [Descripcion del Proyecto](#descripcion)
2. [Stack Tecnologico](#stack)
3. [Estructura de la Base de Datos](#base-de-datos)
4. [Modelos y Relaciones](#modelos)
5. [Controladores y Metodos](#controladores)
6. [Rutas de la API](#rutas)
7. [Seguridad y Middleware](#seguridad)
8. [Sistema de Gamificacion](#gamificacion)
9. [Conceptos Laravel Aplicados](#conceptos)

---

## 1. Descripcion del Proyecto

RetoMap es una aplicacion de gamificacion geolocalizacion. Los usuarios exploran lugares fisicos en Colombia, completan retos en esos lugares, ganan XP (puntos de experiencia), suben de nivel y canjean beneficios con aliados comerciales.

**Flujo principal:**
```
Usuario se registra → Explora el mapa → Ve retos en lugares → 
Completa reto + sube evidencia → Admin aprueba → Usuario gana XP → 
Sube de nivel → Canjea beneficios con aliados
```

---

## 2. Stack Tecnologico

| Componente | Tecnologia |
|------------|------------|
| Framework  | Laravel 11 |
| Autenticacion | Laravel Sanctum (tokens Bearer) |
| Base de datos | MySQL |
| PHP | 8.2+ |
| Almacenamiento | Laravel Storage (disco public) |
| Proteccion | Rate Limiting nativo de Laravel |

---

## 3. Estructura de la Base de Datos

### Tabla: `usuarios`
| Columna | Tipo | Descripcion |
|---------|------|-------------|
| id | BIGINT PK | ID autoincremental |
| nombre_usuario | VARCHAR(100) | Nombre completo |
| email_usuario | VARCHAR(150) UNIQUE | Email de login |
| username_usuario | VARCHAR(50) UNIQUE | Nombre de usuario publico |
| password_hash_usuario | TEXT | Contraseña hasheada con bcrypt |
| avatar_url_usuario | TEXT NULL | URL de foto de perfil |
| xp_total_usuario | INT UNSIGNED | Puntos de experiencia acumulados |
| racha_dias_usuario | SMALLINT | Dias consecutivos activo |
| nivel_id | BIGINT FK | Nivel actual (FK → niveles) |
| ciudad_usuario | VARCHAR(100) NULL | Ciudad del usuario |
| activo_usuario | BOOLEAN | Si la cuenta esta activa |
| ultimo_acceso_usuario | TIMESTAMP NULL | Ultimo login |
| es_admin | BOOLEAN | Si tiene permisos de administrador |
| deleted_at | TIMESTAMP NULL | SoftDelete |

### Tabla: `niveles`
| Columna | Tipo | Descripcion |
|---------|------|-------------|
| id | BIGINT PK | |
| nombre_nivel | VARCHAR | Nombre del nivel (Explorador, etc.) |
| xp_requerido_nivel | INT | XP minimo para alcanzar este nivel |
| insignia_url_nivel | TEXT NULL | Icono del nivel |

### Tabla: `aliados`
| Columna | Tipo | Descripcion |
|---------|------|-------------|
| id | BIGINT PK | |
| nombre_negocio_aliado | VARCHAR(150) | Nombre del negocio |
| tipo_negocio_aliado | VARCHAR(100) | Restaurante, tienda, etc. |
| descripcion_aliado | TEXT NULL | Descripcion del negocio |
| contacto_email_aliado | VARCHAR NULL | Email de contacto |
| contacto_telefono_aliado | VARCHAR(20) NULL | Telefono |
| logo_url_aliado | TEXT NULL | URL del logo |
| sitio_web_aliado | TEXT NULL | Sitio web |
| municipio_aliado | VARCHAR(100) | Municipio donde opera |
| activo_aliado | BOOLEAN | Si esta activo |
| deleted_at | TIMESTAMP NULL | SoftDelete |

### Tabla: `categorias`
| Columna | Tipo | Descripcion |
|---------|------|-------------|
| id | BIGINT PK | |
| nombre_categoria | VARCHAR | Naturaleza, Gastronomia, Arte, etc. |

### Tabla: `lugares`
| Columna | Tipo | Descripcion |
|---------|------|-------------|
| id | BIGINT PK | |
| nombre_lugar | VARCHAR(150) | Nombre del lugar |
| descripcion_lugar | TEXT NULL | Descripcion |
| latitud_lugar | FLOAT | Coordenada GPS |
| longitud_lugar | FLOAT | Coordenada GPS |
| direccion_lugar | VARCHAR(255) NULL | Direccion textual |
| municipio_lugar | VARCHAR(100) | Municipio |
| departamento_lugar | VARCHAR(100) | Departamento |
| verificado_lugar | BOOLEAN | Si fue verificado por el equipo |
| imagen_url_lugar | TEXT NULL | Foto del lugar |
| horario_lugar | VARCHAR(200) NULL | Horario de atencion |
| calificacion_promedio_lugar | INT | Calificacion promedio |
| categoria_id | BIGINT FK | FK → categorias |
| aliado_id | BIGINT FK NULL | FK → aliados (opcional) |
| deleted_at | TIMESTAMP NULL | SoftDelete |

### Tabla: `tipo_retos`
| Columna | Tipo | Descripcion |
|---------|------|-------------|
| id | BIGINT PK | |
| nombre_tipo_reto | VARCHAR | Fotografia, Video, Visita, etc. |

### Tabla: `retos`
| Columna | Tipo | Descripcion |
|---------|------|-------------|
| id | BIGINT PK | |
| nombre_reto | VARCHAR(150) | Titulo del reto |
| descripcion_reto | TEXT | Descripcion detallada |
| xp_recompensa_reto | SMALLINT | XP que da al completarse |
| instruccion_evidencia_reto | TEXT NULL | Como subir la evidencia |
| expira_en_reto | TIMESTAMP NULL | Fecha de expiracion |
| activo_reto | BOOLEAN | Si esta disponible |
| veces_completado_reto | INT | Contador de completados aprobados |
| dificultad_reto | TINYINT | 1=Facil, 2=Medio, 3=Dificil |
| lugar_id | BIGINT FK | FK → lugares |
| tipo_reto_id | BIGINT FK NULL | FK → tipo_retos |
| deleted_at | TIMESTAMP NULL | SoftDelete |

### Tabla: `reto_completados`
| Columna | Tipo | Descripcion |
|---------|------|-------------|
| id | BIGINT PK | |
| usuario_id | BIGINT FK | FK → usuarios |
| reto_id | BIGINT FK | FK → retos |
| evidencia_url_reto_completado | TEXT NULL | URL de la evidencia subida |
| xp_ganado_reto_completado | SMALLINT | XP ganado (copia del reto) |
| estado_reto_completado | ENUM | pendiente / aprobado / rechazado |
| motivo_rechazo_reto_completado | TEXT NULL | Razon del rechazo |
| UNIQUE | (usuario_id, reto_id) | Un usuario no repite reto |

### Tabla: `publicaciones`
| Columna | Tipo | Descripcion |
|---------|------|-------------|
| id | BIGINT PK | |
| usuario_id | BIGINT FK | FK → usuarios |
| reto_completado_id | BIGINT FK NULL | FK → reto_completados |
| caption_publicacion | TEXT NULL | Texto de la publicacion |
| imagen_url_publicacion | TEXT NULL | URL de la imagen |
| likes_cache_publicacion | INT | Cache del contador de likes |
| visible_publicacion | BOOLEAN | Si es visible en el feed |
| deleted_at | TIMESTAMP NULL | SoftDelete |

### Tabla: `like_publicaciones`
| Columna | Tipo | Descripcion |
|---------|------|-------------|
| usuario_id | BIGINT PK compuesta | FK → usuarios |
| publicacion_id | BIGINT PK compuesta | FK → publicaciones |
| created_at | TIMESTAMP | Cuando dio like |

### Tabla: `comentarios`
| Columna | Tipo | Descripcion |
|---------|------|-------------|
| id | BIGINT PK | |
| publicacion_id | BIGINT FK | FK → publicaciones |
| usuario_id | BIGINT FK | FK → usuarios |
| contenido_comentario | TEXT | Texto del comentario |
| visible_comentario | BOOLEAN | Si es visible |
| deleted_at | TIMESTAMP NULL | SoftDelete |

### Tabla: `amistades`
| Columna | Tipo | Descripcion |
|---------|------|-------------|
| id | BIGINT PK | |
| solicitante_id | BIGINT FK | Usuario que envio la solicitud |
| receptor_id | BIGINT FK | Usuario que recibio la solicitud |
| estado_amistad | ENUM | pendiente / aceptada / bloqueada |
| UNIQUE | (solicitante_id, receptor_id) | No duplicar solicitudes |

### Tabla: `insignias`
| Columna | Tipo | Descripcion |
|---------|------|-------------|
| id | BIGINT PK | |
| nombre_insignia | VARCHAR(100) UNIQUE | Nombre del logro |
| descripcion_insignia | TEXT NULL | Que hay que hacer para obtenerla |
| icono_insignia | VARCHAR(10) NULL | Emoji del icono |
| condicion_insignia | TEXT NULL | Condicion tecnica |
| categoria_insignia | VARCHAR(50) NULL | Categoria |

### Tabla: `usuario_insignias` (pivot)
| Columna | Tipo | Descripcion |
|---------|------|-------------|
| usuario_id | BIGINT PK compuesta | FK → usuarios |
| insignia_id | BIGINT PK compuesta | FK → insignias |
| obtenida_en_usuario_insignia | TIMESTAMP | Cuando se obtuvo |

### Tabla: `beneficios`
| Columna | Tipo | Descripcion |
|---------|------|-------------|
| id | BIGINT PK | |
| aliado_id | BIGINT FK | FK → aliados |
| descripcion_beneficio | TEXT | Descripcion del beneficio |
| costo_xp_beneficio | INT | Cuanto XP cuesta canjear |
| valido_hasta_beneficio | DATE NULL | Fecha de expiracion |
| cantidad_disponible_beneficio | INT NULL | Stock (null = ilimitado) |
| cantidad_canjeada_beneficio | INT | Cuantos se han canjeado |
| activo_beneficio | BOOLEAN | Si esta activo |
| deleted_at | TIMESTAMP NULL | SoftDelete |

### Tabla: `canjes`
| Columna | Tipo | Descripcion |
|---------|------|-------------|
| id | BIGINT PK | |
| usuario_id | BIGINT FK | FK → usuarios |
| beneficio_id | BIGINT FK | FK → beneficios |
| xp_descontado_canje | INT | XP que se descontaron |
| codigo_unico_canje | VARCHAR(20) UNIQUE | Codigo para redimir en el aliado |
| usado_canje | BOOLEAN | Si ya fue usado |
| usado_en_canje | TIMESTAMP NULL | Cuando fue usado |

### Tabla: `notificaciones`
| Columna | Tipo | Descripcion |
|---------|------|-------------|
| id | BIGINT PK | |
| usuario_id | BIGINT FK | FK → usuarios |
| tipo_notificacion | ENUM | like / comentario / amistad_solicitud / amistad_aceptada / reto_nuevo / insignia / nivel_subido / beneficio |
| titulo_notificacion | VARCHAR(150) | Titulo corto |
| cuerpo_notificacion | TEXT NULL | Texto completo |
| entidad_tipo_notificacion | VARCHAR(50) NULL | Tipo de entidad relacionada |
| entidad_id_notificacion | BIGINT NULL | ID de la entidad relacionada |
| leida_notificacion | BOOLEAN | Si fue leida |
| leida_en_notificacion | TIMESTAMP NULL | Cuando fue leida |

### Tabla: `password_reset_tokens`
| Columna | Tipo | Descripcion |
|---------|------|-------------|
| email | VARCHAR PK | Email del usuario |
| token | VARCHAR | Token hasheado con bcrypt |
| created_at | TIMESTAMP NULL | Cuando se genero |

---

## 4. Modelos y Relaciones

### `Usuario`
- `belongsTo` → Nivel
- `hasMany` → RetoCompletado
- `hasMany` → Publicacion
- `hasMany` → Amistad (como solicitante)
- `hasMany` → Amistad (como receptor)
- `hasMany` → Canje
- `hasMany` → Notificacion
- `hasMany` → LikePublicacion
- `belongsToMany` → Insignia (via usuario_insignias)

**Metodos helper:**
- `getAuthPassword()` — retorna `password_hash_usuario` para que Sanctum lo use en autenticacion

### `Reto`
- `belongsTo` → Lugar
- `belongsTo` → TipoReto
- `hasMany` → RetoCompletado

**Metodos helper:**
- `estaVigente(): bool` — verifica si el reto esta activo y no ha expirado
- `scopeActivos($query)` — scope para filtrar solo retos activos y no expirados

### `RetoCompletado`
- `belongsTo` → Usuario
- `belongsTo` → Reto
- `hasOne` → Publicacion

**Scopes:**
- `scopeAprobados($query)` — filtra solo los aprobados

### `Lugar`
- `belongsTo` → Categoria
- `belongsTo` → Aliado (nullable)
- `hasMany` → Reto

**Metodos helper:**
- `distanciaDesde(float $lat, float $lng): float` — calcula distancia en km usando formula Haversine

### `Aliado`
- `hasMany` → Lugar
- `hasMany` → Beneficio

### `Beneficio`
- `belongsTo` → Aliado
- `hasMany` → Canje

**Metodos helper:**
- `tieneStock(): bool` — verifica si hay stock disponible (null = ilimitado)
- `estaVigente(): bool` — verifica si esta activo y no ha expirado

### `Publicacion`
- `belongsTo` → Usuario
- `belongsTo` → RetoCompletado (nullable)
- `hasMany` → LikePublicacion
- `hasMany` → Comentario

### `Amistad`
- `belongsTo` → Usuario (solicitante)
- `belongsTo` → Usuario (receptor)

### `Notificacion`
- `belongsTo` → Usuario

### `Canje`
- `belongsTo` → Usuario
- `belongsTo` → Beneficio

---

## 5. Controladores y Metodos

### `AuthController`
Maneja registro, login y logout de usuarios.

| Metodo | Descripcion | Variables clave |
|--------|-------------|-----------------|
| `register(Request $r)` | Crea nuevo usuario. Hashea la contrasena con `Hash::make()`. Crea token Sanctum. | `nombre_usuario`, `email_usuario`, `username_usuario`, `password_hash_usuario` |
| `login(Request $r)` | Verifica credenciales con `Hash::check()`. Retorna token Bearer. | `email`, `password` |
| `logout(Request $r)` | Revoca el token actual con `$r->user()->currentAccessToken()->delete()` | — |
| `me(Request $r)` | Retorna datos del usuario autenticado con nivel cargado | — |

### `PerfilController`
Gestiona perfiles de usuario, ranking y busqueda.

| Metodo | Descripcion | Variables clave |
|--------|-------------|-----------------|
| `miPerfil(Request $r)` | Perfil completo con estadisticas: retos aprobados, publicaciones, posicion en ranking | — |
| `actualizar(Request $r)` | Edita nombre, username, ciudad, avatar. Valida `unique` ignorando el usuario actual | `nombre_usuario`, `username_usuario`, `ciudad_usuario`, `avatar_url_usuario` |
| `cambiarPassword(Request $r)` | Verifica password actual, cambia, revoca TODOS los tokens | `password_actual`, `password_nuevo`, `password_nuevo_confirmation` |
| `verPerfil(int $id)` | Perfil publico de otro usuario (sin email ni datos privados) | `id` de URL |
| `ranking(Request $r)` | Lista usuarios ordenados por XP descendente, paginado de 20 | — |
| `miPosicion(Request $r)` | Posicion exacta del usuario en el ranking + XP para siguiente nivel | — |
| `buscarUsuarios(Request $r)` | Busca por nombre o username con LIKE. Query param: `?q=texto` | `q` (min 2 chars) |

### `RetoController`
Listado y completado de retos para usuarios normales.

| Metodo | Descripcion | Variables clave |
|--------|-------------|-----------------|
| `index(Request $r)` | Lista retos activos con scope `activos()`. Filtra por lugar_id, dificultad, tipo | `lugar_id`, `dificultad`, `tipo_reto_id` (query params) |
| `show(int $id)` | Detalle de un reto con lugar y tipo. Verifica vigencia | `id` de URL |
| `completar(Request $r, int $id)` | Marca reto como completado con estado `pendiente`. Verifica que no lo haya completado antes (unique constraint) | `evidencia_url_reto_completado`, `xp_ganado_reto_completado` |
| `misCompletados(Request $r)` | Lista los retos completados del usuario autenticado | — |

### `AdminRetoController`
CRUD de retos y flujo de aprobacion — solo admins.

| Metodo | Descripcion | Variables clave |
|--------|-------------|-----------------|
| `index(Request $r)` | Lista todos los RetoCompletado con estado `pendiente`, con eager loading de usuario y reto | — |
| `aprobar(Request $r, int $id)` | Cambia estado a `aprobado`. Verifica que estaba en `pendiente`. El Observer detecta el cambio y da XP automaticamente | `id` de URL |
| `rechazar(Request $r, int $id)` | Cambia estado a `rechazado` con motivo opcional. Verifica `pendiente` | `motivo` (body, nullable) |
| `store(Request $r)` | Crea reto nuevo con validacion completa | `nombre_reto`, `descripcion_reto`, `xp_recompensa_reto`, `instruccion_evidencia_reto`, `dificultad_reto`, `lugar_id`, `tipo_reto_id`, `expira_en_reto`, `activo_reto` |
| `update(Request $r, int $id)` | Edita reto con `sometimes` (campos opcionales). Usa `fill() + save()` | mismos campos con `sometimes` |
| `destroy(int $id)` | SoftDelete del reto | `id` de URL |

### `AdminLugarController`
CRUD de lugares — solo admins.

| Metodo | Descripcion | Variables clave |
|--------|-------------|-----------------|
| `store(Request $r)` | Crea lugar con coordenadas GPS. Valida `between:-90,90` para latitud | `nombre_lugar`, `latitud_lugar`, `longitud_lugar`, `municipio_lugar`, `departamento_lugar`, `categoria_id`, `aliado_id` |
| `update(Request $r, int $id)` | Edita lugar. Todos los campos `sometimes` | mismos campos |
| `destroy(int $id)` | SoftDelete | `id` de URL |

### `AdminAliadoController`
CRUD de aliados — solo admins.

| Metodo | Descripcion | Variables clave |
|--------|-------------|-----------------|
| `index(Request $r)` | Lista todos los aliados ordenados por fecha de creacion descendente, paginado | — |
| `store(Request $r)` | Crea aliado. Valida `email` y `url` en campos correspondientes | `nombre_negocio_aliado`, `tipo_negocio_aliado`, `contacto_email_aliado`, `sitio_web_aliado`, `municipio_aliado`, `activo_aliado` |
| `update(Request $r, int $id)` | Edita aliado | mismos campos con `sometimes` |
| `destroy(int $id)` | SoftDelete | `id` de URL |

### `AdminBeneficioController`
CRUD de beneficios — solo admins.

| Metodo | Descripcion | Variables clave |
|--------|-------------|-----------------|
| `store(Request $r)` | Crea beneficio con costo en XP y stock opcional | `aliado_id`, `descripcion_beneficio`, `costo_xp_beneficio`, `valido_hasta_beneficio`, `cantidad_disponible_beneficio`, `activo_beneficio` |
| `update(Request $r, int $id)` | Edita beneficio | mismos con `sometimes` |
| `destroy(int $id)` | SoftDelete | `id` de URL |

### `LugarController`
Consulta de lugares para usuarios normales.

| Metodo | Descripcion | Variables clave |
|--------|-------------|-----------------|
| `index(Request $r)` | Lista lugares con filtros de municipio y categoria | `municipio`, `categoria_id` (query params) |
| `show(int $id)` | Detalle de lugar con aliado y retos activos | `id` de URL |
| `cercanos(Request $r)` | Lugares ordenados por distancia usando formula Haversine. Requiere lat/lng | `lat`, `lng`, `radio_km` (query params) |
| `municipios(Request $r)` | Lista de municipios unicos disponibles | — |

### `BeneficioController`
Consulta y canje de beneficios para usuarios.

| Metodo | Descripcion | Variables clave |
|--------|-------------|-----------------|
| `index(Request $r)` | Lista beneficios activos y vigentes. Indica si el usuario puede canjear cada uno | `aliado_id`, `max_xp` (filtros opcionales) |
| `show(Request $r, int $id)` | Detalle con flag `ya_canjee` para saber si el usuario ya lo canjeo | `id` de URL |
| `canjear(Request $r, int $id)` | Descuenta XP, incrementa stock canjeado, genera codigo unico de 8 chars. Todo en `DB::transaction()` | `id` de URL |
| `misCanjes(Request $r)` | Historial de canjes del usuario con estado (usado/no usado) | — |
| `usarCanje(Request $r, string $codigo)` | Marca un canje como usado (lo hace el aliado en su negocio) | `codigo` de URL |

### `PublicacionController`
Feed social y publicaciones.

| Metodo | Descripcion | Variables clave |
|--------|-------------|-----------------|
| `feed(Request $r)` | Feed paginado de publicaciones visibles ordenadas por fecha | — |
| `store(Request $r)` | Crea publicacion opcionalmente asociada a un reto completado | `caption_publicacion`, `imagen_url_publicacion`, `reto_completado_id` |
| `show(int $id)` | Detalle de publicacion con likes y comentarios | `id` de URL |
| `porUsuario(int $id)` | Publicaciones de un usuario especifico | `id` de URL |
| `destroy(Request $r, int $id)` | Elimina publicacion propia (SoftDelete) | `id` de URL |
| `toggleLike(Request $r, int $id)` | Da o quita like. Si existe lo elimina, si no existe lo crea | `id` de URL |
| `comentar(Request $r, int $id)` | Agrega comentario a una publicacion | `contenido_comentario` |

### `AmistadController`
Sistema de amistades entre usuarios.

| Metodo | Descripcion | Variables clave |
|--------|-------------|-----------------|
| `misAmigos(Request $r)` | Lista amistades con estado `aceptada` | — |
| `pendientes(Request $r)` | Solicitudes recibidas pendientes | — |
| `estado(Request $r, int $id)` | Estado de la relacion con un usuario especifico | `id` de URL |
| `solicitar(Request $r, int $id)` | Envia solicitud de amistad. Verifica que no exista ya | `id` de URL |
| `aceptar(Request $r, int $id)` | Acepta solicitud recibida. Solo el receptor puede aceptar | `id` de URL |
| `rechazar(Request $r, int $id)` | Rechaza o cancela solicitud | `id` de URL |
| `bloquear(Request $r, int $id)` | Bloquea a un usuario. Cambia estado a `bloqueada` | `id` de URL |
| `eliminar(Request $r, int $id)` | Elimina una amistad existente | `id` de URL |

### `NotificacionController`
Sistema de notificaciones in-app.

| Metodo | Descripcion | Variables clave |
|--------|-------------|-----------------|
| `index(Request $r)` | Lista notificaciones del usuario, mas recientes primero | — |
| `contador(Request $r)` | Numero de notificaciones no leidas | — |
| `marcarLeida(Request $r, int $id)` | Marca una notificacion como leida con timestamp | `id` de URL |
| `marcarTodasLeidas(Request $r)` | Marca todas las notificaciones como leidas | — |
| `limpiarLeidas(Request $r)` | Elimina todas las notificaciones ya leidas | — |
| `destroy(Request $r, int $id)` | Elimina una notificacion especifica | `id` de URL |

### `PasswordResetController`
Recuperacion de contraseña por token.

| Metodo | Descripcion | Variables clave |
|--------|-------------|-----------------|
| `forgot(Request $r)` | Genera token aleatorio de 64 chars, lo hashea y guarda. Retorna token en respuesta (en prod se enviaria por email) | `email` |
| `reset(Request $r)` | Verifica token, verifica expiracion (60 min), cambia contraseña, elimina token | `email`, `token`, `password_nuevo`, `password_nuevo_confirmation` |

### `UploadController`
Subida de archivos de evidencia.

| Metodo | Descripcion | Variables clave |
|--------|-------------|-----------------|
| `evidencia(Request $r)` | Valida imagen/video (max 5MB), guarda en `storage/app/public/evidencias/`, retorna URL publica | `archivo` (multipart/form-data) |

---

## 6. Rutas de la API

### Rutas Publicas (sin token)
```
POST   /api/register              → AuthController@register
POST   /api/login                 → AuthController@login  [throttle: 5/min por IP]
POST   /api/password/forgot       → PasswordResetController@forgot
POST   /api/password/reset        → PasswordResetController@reset
```

### Rutas Protegidas (requieren Bearer token) [throttle: 60/min]
```
POST   /api/logout                → AuthController@logout
GET    /api/me                    → AuthController@me

GET    /api/lugares               → LugarController@index
GET    /api/lugares/cercanos      → LugarController@cercanos
GET    /api/lugares/municipios    → LugarController@municipios
GET    /api/lugares/{id}          → LugarController@show

GET    /api/retos                 → RetoController@index
GET    /api/retos/completados     → RetoController@misCompletados
GET    /api/retos/{id}            → RetoController@show
POST   /api/retos/{id}/completar  → RetoController@completar

GET    /api/feed                  → PublicacionController@feed
POST   /api/publicaciones         → PublicacionController@store
GET    /api/publicaciones/{id}    → PublicacionController@show
GET    /api/publicaciones/usuario/{id} → PublicacionController@porUsuario
DELETE /api/publicaciones/{id}    → PublicacionController@destroy
POST   /api/publicaciones/{id}/like    → PublicacionController@toggleLike
POST   /api/publicaciones/{id}/comentar → PublicacionController@comentar

GET    /api/perfil                → PerfilController@miPerfil
PUT    /api/perfil                → PerfilController@actualizar
PUT    /api/perfil/password       → PerfilController@cambiarPassword
GET    /api/perfil/buscar         → PerfilController@buscarUsuarios  [?q=texto]
GET    /api/perfil/{id}           → PerfilController@verPerfil

GET    /api/ranking               → PerfilController@ranking
GET    /api/ranking/mi-posicion   → PerfilController@miPosicion

GET    /api/amistades             → AmistadController@misAmigos
GET    /api/amistades/pendientes  → AmistadController@pendientes
GET    /api/amistades/estado/{id} → AmistadController@estado
POST   /api/amistades/solicitar/{id} → AmistadController@solicitar
PUT    /api/amistades/{id}/aceptar   → AmistadController@aceptar
DELETE /api/amistades/{id}/rechazar  → AmistadController@rechazar
PUT    /api/amistades/bloquear/{id}  → AmistadController@bloquear
DELETE /api/amistades/{id}           → AmistadController@eliminar

GET    /api/beneficios               → BeneficioController@index
GET    /api/beneficios/mis-canjes    → BeneficioController@misCanjes
GET    /api/beneficios/{id}          → BeneficioController@show
POST   /api/beneficios/{id}/canjear  → BeneficioController@canjear
PUT    /api/beneficios/canjes/{codigo}/usar → BeneficioController@usarCanje

GET    /api/notificaciones           → NotificacionController@index
GET    /api/notificaciones/contador  → NotificacionController@contador
PUT    /api/notificaciones/leer-todas → NotificacionController@marcarTodasLeidas
DELETE /api/notificaciones/limpiar/leidas → NotificacionController@limpiarLeidas
PUT    /api/notificaciones/{id}/leer  → NotificacionController@marcarLeida
DELETE /api/notificaciones/{id}       → NotificacionController@destroy

POST   /api/upload/evidencia         → UploadController@evidencia
```

### Rutas Admin (requieren Bearer token + es_admin = true)
```
GET    /api/admin/retos-completados              → AdminRetoController@index
PUT    /api/admin/retos-completados/{id}/aprobar → AdminRetoController@aprobar
PUT    /api/admin/retos-completados/{id}/rechazar → AdminRetoController@rechazar
POST   /api/admin/retos                          → AdminRetoController@store
PUT    /api/admin/retos/{id}                     → AdminRetoController@update
DELETE /api/admin/retos/{id}                     → AdminRetoController@destroy

GET    /api/admin/aliados                        → AdminAliadoController@index
POST   /api/admin/aliados                        → AdminAliadoController@store
PUT    /api/admin/aliados/{id}                   → AdminAliadoController@update
DELETE /api/admin/aliados/{id}                   → AdminAliadoController@destroy

POST   /api/admin/lugares                        → AdminLugarController@store
PUT    /api/admin/lugares/{id}                   → AdminLugarController@update
DELETE /api/admin/lugares/{id}                   → AdminLugarController@destroy

POST   /api/admin/beneficios                     → AdminBeneficioController@store
PUT    /api/admin/beneficios/{id}                → AdminBeneficioController@update
DELETE /api/admin/beneficios/{id}                → AdminBeneficioController@destroy
```

---

## 7. Seguridad y Middleware

### Laravel Sanctum
Sistema de autenticacion por tokens Bearer. Cada login genera un token unico almacenado en la tabla `personal_access_tokens`. El token se envia en cada peticion en el header:
```
Authorization: Bearer <token>
```

### AdminMiddleware (`app/Http/Middleware/AdminMiddleware.php`)
Verifica que el usuario autenticado tenga `es_admin = true`. Si no, retorna `403 Forbidden`. Se aplica solo a las rutas del grupo `/api/admin/*`.

Registrado en `bootstrap/app.php` con el alias `admin`.

### Rate Limiting (`app/Providers/AppServiceProvider.php`)
Dos limitadores configurados:
- `login` → 5 intentos por minuto por IP. Protege contra fuerza bruta en el login.
- `api` → 60 peticiones por minuto por ID de usuario (o IP si no esta autenticado). Protege todos los endpoints protegidos.

Si se supera el limite → respuesta `429 Too Many Requests`.

### SoftDeletes
Los modelos `Usuario`, `Reto`, `Lugar`, `Aliado`, `Beneficio`, `Publicacion`, `Comentario` usan `SoftDeletes`. Al llamar `->delete()` no se borra el registro — solo se pone la fecha en `deleted_at`. Las consultas normales lo excluyen automaticamente.

### Password Hashing
Todas las contrasenas se hashean con `Hash::make()` (bcrypt). Nunca se almacena texto plano.

---

## 8. Sistema de Gamificacion

### Observer: `RetoCompletadoObserver`
Se ejecuta automaticamente cuando cambia un `RetoCompletado`. Registrado en `AppServiceProvider::boot()`.

**`updated()`** — Se dispara cuando se actualiza un RetoCompletado:
1. Verifica que el campo `estado_reto_completado` haya cambiado (`wasChanged()`)
2. Verifica que el nuevo estado sea `aprobado`
3. Llama a `procesarXp()`

**`procesarXp()`** — Logica central de gamificacion:
1. Suma `xp_ganado_reto_completado` al `xp_total_usuario` del usuario
2. Busca el nivel mas alto alcanzable con el nuevo XP total
3. Actualiza `xp_total_usuario` y `nivel_id` del usuario
4. Incrementa `veces_completado_reto` en el reto
5. Si el usuario subio de nivel → crea una notificacion de tipo `nivel_subido`

### Flujo completo de XP:
```
1. Usuario llama POST /api/retos/{id}/completar
2. Se crea RetoCompletado con estado='pendiente'
3. Admin llama PUT /api/admin/retos-completados/{id}/aprobar
4. Se cambia estado a 'aprobado' y se llama save()
5. El Observer detecta el cambio via wasChanged('estado_reto_completado')
6. Se suma XP, se recalcula nivel, se notifica si subio
```

### Beneficios con XP:
```
1. Usuario acumula XP completando retos
2. Ve beneficios disponibles en /api/beneficios
3. Llama POST /api/beneficios/{id}/canjear
4. En DB::transaction(): se descuenta XP, se incrementa stock canjeado, se genera codigo
5. Usuario muestra codigo al aliado
6. Aliado llama PUT /api/beneficios/canjes/{codigo}/usar
7. Canje queda marcado como usado
```

---

## 9. Conceptos Laravel Aplicados

| Concepto | Donde se usa | Para que sirve |
|----------|-------------|----------------|
| **Migrations** | `database/migrations/` | Define y versiona el esquema de BD. Cada migration tiene `up()` (aplicar) y `down()` (revertir) |
| **Eloquent ORM** | Todos los modelos | Mapea tablas a clases PHP. `::find()`, `::where()`, `->save()`, `->delete()` |
| **Relaciones Eloquent** | Todos los modelos | `hasMany`, `belongsTo`, `belongsToMany` definen las FK como metodos PHP |
| **Scopes** | `Reto`, `RetoCompletado` | Metodos reutilizables de query. `Reto::activos()->get()` |
| **SoftDeletes** | 6 modelos | `deleted_at` en vez de borrar. Permite recuperar registros |
| **Sanctum** | `AuthController` | Tokens Bearer para APIs. Mas ligero que JWT |
| **Middleware** | `AdminMiddleware` | Filtros que se ejecutan antes del controlador |
| **Observer** | `RetoCompletadoObserver` | Escucha eventos del modelo (created, updated, deleted) |
| **DB::transaction()** | `BeneficioController@canjear` | Si algo falla dentro, todo se revierte. Atomicidad |
| **Eager Loading** (`with()`) | Varios | Carga relaciones en una sola query. Evita el problema N+1 |
| **Paginate** | Todos los index | Divide resultados en paginas. Retorna metadata de paginacion |
| **$fillable** | Todos los modelos | Lista blanca de campos asignables masivamente. Seguridad |
| **$casts** | Todos los modelos | Convierte tipos de BD a tipos PHP (boolean, integer, datetime) |
| **$hidden** | `Usuario` | Campos que nunca se incluyen en respuestas JSON |
| **findOrFail()** | Controladores | Si no existe → 404 automatico. Mas seguro que find() |
| **validate()** | Controladores | Si falla → 422 automatico con errores detallados |
| **Rate Limiter** | `AppServiceProvider` | Limita peticiones por IP o usuario |
| **Storage** | `UploadController` | Sistema de discos para archivos. `storage:link` crea symlink publico |
| **Hash::make/check** | Auth, PasswordReset | bcrypt para contrasenas |
| **Str::random()** | `PasswordResetController` | Genera strings aleatorios seguros |
| **Carbon** | `PasswordResetController` | Manejo de fechas. `diffInMinutes()`, `isPast()`, etc. |
| **orWhere** | `buscarUsuarios` | Condicion OR en queries Eloquent |
| **LIKE con %** | `buscarUsuarios` | Busqueda de texto parcial en SQL |
| **updateOrInsert** | `PasswordResetController` | Inserta o actualiza segun condicion. Un token por email |
| **wasChanged()** | `RetoCompletadoObserver` | Detecta si un campo especifico cambio en el ultimo save() |
| **increment/decrement** | Observer, Beneficio | Incrementa/decrementa un campo numerico atomicamente |
| **nullable()** | Muchos campos | El campo acepta NULL en la BD |
| **unique constraint** | `reto_completados` | Un usuario no puede completar el mismo reto dos veces |
| **Haversine** | `Lugar@distanciaDesde` | Formula matematica para calcular distancia entre dos puntos GPS |
| **prefix()** | `routes/api.php` | Agrupa rutas bajo un prefijo de URL comun |
| **Alias de middleware** | `bootstrap/app.php` | Nombre corto para usar el middleware en rutas |
