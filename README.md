# Siroko Code Challenge - Cart & Checkout API

API REST para gestión de carrito de compras y proceso de checkout con arquitectura hexagonal y DDD.

---

## 📋 Descripción del Proyecto

Sistema de carrito de compras y checkout desarrollado con **Symfony 7.3** siguiendo principios de **Domain-Driven Design (DDD)** y **Arquitectura Hexagonal**. La API permite gestionar productos en un carrito, realizar checkout y procesar pagos de forma asíncrona.

### Características Principales

- ✅ **Gestión completa de carrito**: Añadir, actualizar, eliminar y consultar productos
- ✅ **Proceso de checkout**: Conversión del carrito en orden con procesamiento asíncrono de pagos (de momento simulado)
- ✅ **Autenticación JWT**: Sistema de login/logout con tokens revocables
- ✅ **CRUD de Productos**: Gestión completa de catálogo (admin)
- ✅ **Mensajería asíncrona**: Procesamiento de pagos con Symfony Messenger
- ✅ **Testing exhaustivo**: 85 tests con cobertura de casos de uso
- ✅ **OpenAPI/Swagger**: Documentación interactiva completa

> **📝 Nota sobre Carritos Anónimos**: El modelado del dominio del carrito está **preparado para gestionar carritos sin autenticación** (carritos anónimos). El agregado `Cart` tiene un campo `userId` opcional (`?string`) que permite esta funcionalidad. Sin embargo, la implementación actual requiere autenticación para simplificar el MVP y priorizar el *time to market*. La funcionalidad de carritos anónimos está planificada para una siguiente iteración, donde se implementará la gestión de sesiones anónimas y la posterior conversión/merge del carrito al hacer login.

> **💶 Nota sobre IVA**: La implementación actual trabaja con **precios con IVA incluido** (21% estándar en España). No se ha implementado la gestión de productos con diferentes tipos de IVA (10%, 4%, exento) para simplificar el MVP. Todos los precios almacenados y mostrados ya incluyen el IVA del 21%. La separación del IVA y el soporte para múltiples tipos impositivos está planificada para futuras iteraciones, donde se implementará el desglose del precio base + IVA en facturas y reportes.

---

## 🏗️ Arquitectura y Diseño

### Arquitectura Hexagonal

El proyecto está estructurado siguiendo los principios de **Arquitectura Hexagonal (Ports & Adapters)**:

```
src/
├── Cart/
│   ├── Domain/              # Lógica de negocio pura
│   │   ├── Cart.php         # Agregado raíz
│   │   ├── CartItem.php     # Entidad
│   │   ├── Money.php        # Value Object
│   │   ├── CartId.php       # Value Object
│   │   └── Port/            # Interfaces (puertos)
│   ├── Application/         # Casos de uso (CQRS)
│   │   ├── Command/         # Commands (escritura)
│   │   ├── Query/           # Queries (lectura)
│   │   ├── Handler/         # Command/Query Handlers
│   │   └── Http/            # Controllers (adaptador HTTP)
│   └── Infrastructure/      # Implementaciones técnicas
│       └── DoctrineCartRepository.php
├── Order/
│   ├── Domain/
│   │   ├── Order.php        # Agregado raíz
│   │   ├── OrderStatus.php  # Enum de estados
│   │   └── OrderId.php      # Value Object
│   ├── Application/
│   └── Infrastructure/
├── Product/
│   ├── Domain/
│   ├── Application/
│   └── Infrastructure/
└── Auth/
    ├── Domain/
    ├── Application/
    └── Infrastructure/
```

### Patrones DDD Implementados

#### 1. **Aggregates (Agregados)**
- `Cart`: Agregado raíz que gestiona items del carrito
- `Order`: Agregado raíz que gestiona el ciclo de vida de una orden
- `Product`: Agregado raíz del catálogo

#### 2. **Entities (Entidades)**
- `CartItem`: Producto dentro del carrito con cantidad
- `User`: Usuario del sistema

#### 3. **Value Objects**
- `Money`: Representa valores monetarios con validación
- `CartId`, `OrderId`, `ProductId`: Identificadores tipados
- `Email`, `PasswordHash`: Valores de dominio del usuario
- `OrderStatus`: Enum con estados de orden (Pending, Processing, Paid, etc.)

#### 4. **Domain Events**
- `OrderProcessCartMessage`: Evento para procesamiento asíncrono de pagos

#### 5. **Repositories (Ports)**
- `CartRepositoryInterface`: Puerto para persistencia de carritos
- `OrderRepositoryInterface`: Puerto para persistencia de órdenes
- `ProductRepositoryInterface`: Puerto para persistencia de productos

#### 6. **Services**
- `CartResolver`: Resuelve el carrito del usuario autenticado
- `CartSerializer`: Serializa agregados para respuestas HTTP
- `ProductService`: Orquesta operaciones del catálogo

---

## 🎯 CQRS (Command Query Responsibility Segregation)

El proyecto implementa **CQRS** separando operaciones de escritura (Commands) y lectura (Queries):

### Commands (Escritura)
```
AddItemToCartCommand        → AddItemToCartHandler
UpdateItemQuantityCommand   → UpdateItemQuantityHandler
RemoveItemFromCartCommand   → RemoveItemFromCartHandler
CheckoutCartCommand         → CheckoutCartHandler
CancelOrderCommand          → CancelOrderHandler
```

### Queries (Lectura)
```
GetCartQuery                → Resuelto directamente por CartResolver
GetOrderQuery               → GetOrderHandler
```

### Mensajería Asíncrona
```
OrderProcessCartMessage     → OrderProcessCartMessageHandler
  ├─ Valida stock
  ├─ Procesa pago (simulado)
  └─ Actualiza inventario
```

---

## 📊 Modelado del Dominio

Para una descripción completa del modelado del dominio, incluyendo:
- Diagrama de agregados y relaciones
- Patrones DDD implementados (Aggregates, Entities, Value Objects, Domain Events)
- Flujo de checkout y procesamiento de pago
- Transiciones de estado de órdenes
- Bounded Contexts
- Invariantes del dominio

**👉 Ver documentación completa: [DOMAIN_MODELING.md](./DOMAIN_MODELING.md)**

---

## 🚀 Tecnologías Utilizadas

### Backend
- **PHP 8.3**: Lenguaje principal
- **Symfony 7.3**: Framework web
- **Doctrine ORM**: Capa de persistencia
- **Symfony Messenger**: Mensajería asíncrona

### Base de Datos
- **SQLite**: Base de datos para desarrollo/tests
- **MySQL**: Opción para producción (docker-compose)

### Testing
- **PHPUnit 10.5**: Framework de testing
- **Zenstruck Foundry**: Factories para tests
- **85 tests**: Cobertura exhaustiva

### Documentación
- **NelmioApiDocBundle**: Generación OpenAPI
- **Swagger UI**: Interfaz interactiva

### Calidad de Código
- **PHPStan**: Análisis estático (nivel 6)
- **PHP-CS-Fixer**: Code style
- **PHPMD**: Métricas y detección de code smells

### Infraestructura
- **Docker**: Contenedorización
- **Docker Compose**: Orquestación
- **Nginx**: Servidor web

---

## 📖 OpenAPI Specification

### Acceso a la Documentación

**Swagger UI (Interfaz Interactiva)**
```
http://localhost:8080/api/doc
```

**OpenAPI JSON**
```
http://localhost:8080/api/doc.json
```

**OpenAPI YAML**
```
http://localhost:8080/api/doc.yaml
```

### Endpoints Documentados (14 endpoints)

#### 🔐 Autenticación (2)
```
POST   /api/login          # Autenticar usuario (obtener JWT)
POST   /api/logout         # Cerrar sesión (revocar token)
```

#### 🛒 Carrito (6)
```
GET    /api/cart/                      # Obtener carrito actual
POST   /api/cart/items                 # Agregar producto al carrito
PUT    /api/cart/items/{productId}     # Actualizar cantidad (completo)
PATCH  /api/cart/items/{productId}     # Actualizar cantidad (parcial)
DELETE /api/cart/items/{productId}     # Eliminar producto del carrito
POST   /api/cart/checkout               # Realizar checkout
```

#### 📦 Productos (6)
```
GET    /api/products/              # Listar productos (paginado)
GET    /api/products/{id}          # Obtener detalle de producto
POST   /api/products/              # Crear producto (Admin)
PUT    /api/products/{id}          # Actualizar producto completo (Admin)
PATCH  /api/products/{id}          # Actualizar producto parcial (Admin)
DELETE /api/products/{id}          # Eliminar producto (Admin)
```

#### 💚 Health Check (1)
```
GET    /api/health                 # Estado del servicio
```

### Autenticación

Todos los endpoints (excepto `/login` y `/health`) requieren autenticación JWT:

```bash
# 1. Login
curl -X POST http://localhost:8080/api/login \
  -H "Content-Type: application/json" \
  -d '{"email":"user@example.com","password":"password123"}'

# Respuesta:
{
  "token": "eyJ0eXAiOiJKV1QiLCJhbGc...",
  "token_type": "Bearer",
  "expires_in": 3600
}

# 2. Usar token en requests
curl -X GET http://localhost:8080/api/cart/ \
  -H "Authorization: Bearer {token}"
```

---

## 🐳 Instrucciones para Levantar el Entorno

### Requisitos Previos
- Docker
- Docker Compose

### 1. Clonar el Repositorio
```bash
git clone <repository-url>
cd siroko_code_challenge
```

### 2. Levantar el Entorno con Docker Compose
```bash
make up
```

Esto levantará:
- **PHP-FPM 8.3** (puerto interno)
- **Nginx** (puerto 8080)
- **MySQL** (puerto 3306)

### 3. Instalar Dependencias (si es necesario)
```bash
make composer
```

### 4. Ejecutar Migraciones
```bash
make migrate
```

### 5. Crear Usuarios de Prueba (Opcional)
```bash
# Usuario normal
docker exec siroko_code_challenge_php bin/console app:create-user user@example.com password123

# Usuario admin
docker exec siroko_code_challenge_php bin/console app:create-user admin@example.com admin123 --admin
```

### 6. Acceder a la Aplicación

- **API Base**: http://localhost:8080/api
- **Swagger UI**: http://localhost:8080/api/doc
- **Health Check**: http://localhost:8080/api/health

### Comandos Útiles (Makefile)

El proyecto incluye un **Makefile** con comandos útiles para desarrollo:

#### Gestión del Entorno
```bash
# Levantar el entorno (build incluido)
make up

# Levantar con Alpine Linux
make up-alpine

# Build sin caché
make build

# Detener el entorno
make down

# Acceder al contenedor PHP
make bash
```

#### Desarrollo
```bash
# Instalar dependencias de Composer
make composer

# Ejecutar migraciones de base de datos
make migrate

# Cargar fixtures (datos de prueba)
make fixtures

# Ejecutar tests con testdox
make test

# Generar documentación OpenAPI en YAML
make openapi
```

#### Calidad de Código
```bash
# Verificar estilo de código (dry-run, muestra diff)
make cs

# Corregir estilo de código automáticamente
make fix

# Lint de sintaxis PHP
make lint

# Análisis estático con PHPStan
make stan

# Análisis de complejidad y code smells con PHPMD
make md

# Detectar código duplicado (Copy/Paste Detector)
make cpd
```

#### Mensajería Asíncrona
```bash
# Consumir mensajes del queue async (verbose)
make messenger-consume

# Consumir mensajes con límite de 1 hora
make messenger-watch
```

#### Comandos Directos de Symfony (sin Makefile)
```bash
# Ver logs del contenedor
docker-compose logs -f

# Limpiar caché
docker exec siroko_code_challenge_php bin/console cache:clear

# Ver todas las rutas registradas
docker exec siroko_code_challenge_php bin/console debug:router

# Ver servicios del contenedor
docker exec siroko_code_challenge_php bin/console debug:container
```

---

## 🧪 Comando para Lanzar los Tests

### Ejecutar la Suite de Tests Completa

```bash
make test
```

O usando el comando directo:
```bash
docker exec siroko_code_challenge_php vendor/bin/phpunit --colors=always --testdox
```

Este comando ejecutará los **85 tests implementados** que cubren:

- **Auth**: 13 tests (Unit: 5, Integration: 8)
- **Cart**: 10 tests (Unit: 5, Integration: 5)  
- **Product**: 14 tests (Unit: 2, Integration: 12)
- **Order**: 47 tests (Unit: 17, Integration: 26, API: 4)
- **Health**: 1 test (Integration: 1)

### Tipos de Tests Incluidos

1. **Unit Tests**: Lógica de dominio pura (Value Objects, Entities, Enums)
2. **Integration Tests**: Handlers, Services y Repositorios con BD real
3. **API Tests**: Controllers end-to-end con HTTP requests/responses completos

---

## 📁 Estructura del Proyecto

### Visión General

```
siroko_code_challenge/
├── bin/
│   └── console                    # CLI de Symfony
├── config/
│   ├── packages/                  # Configuración por bundles
│   ├── routes.yaml                # Rutas principales
│   └── services.yaml              # Inyección de dependencias
├── docker/
│   ├── Dockerfile                 # Imagen PHP-FPM
│   ├── nginx/                     # Configuración Nginx
│   └── php/                       # Configuración PHP
├── migrations/                    # Migraciones de BD
├── public/
│   └── index.php                  # Front controller
├── src/
│   ├── Auth/                      # Módulo de autenticación
│   ├── Cart/                      # Módulo de carrito
│   ├── Order/                     # Módulo de órdenes
│   ├── Product/                   # Módulo de productos
│   ├── Health/                    # Health check
│   ├── Message/                   # Mensajes asíncronos
│   ├── MessageHandler/            # Handlers de mensajes
│   └── Kernel.php
├── tests/
│   ├── Auth/                      # Tests de autenticación
│   ├── Cart/                      # Tests de carrito
│   ├── Order/                     # Tests de órdenes
│   ├── Product/                   # Tests de productos
│   └── Factory/                   # Factories para tests
├── docker-compose.yml             # Orquestación Docker
├── composer.json                  # Dependencias PHP
├── phpunit.dist.xml               # Configuración PHPUnit
└── README.md                      # Este archivo
```

### Estructura Detallada por Módulo

#### 🔐 Auth - Módulo de Autenticación

```
src/Auth/
├── routes.php                              # Definición de rutas del módulo
├── Application/
│   ├── Http/
│   │   ├── Controller/
│   │   │   ├── AuthController.php          # Login (POST /api/login)
│   │   │   └── LogoutController.php        # Logout (POST /api/logout)
│   │   └── DTO/
│   │       └── LoginRequest.php            # DTO para validación de login
│   └── Security/
│       ├── TokenGeneratorInterface.php     # Puerto para generación JWT
│       ├── TokenRevokerInterface.php       # Puerto para revocación de tokens
│       ├── JwtTokenGenerator.php           # Implementación generador JWT
│       └── DatabaseTokenRevoker.php        # Implementación revocación en BD
├── Domain/
│   ├── User.php                            # Agregado raíz de usuario
│   ├── Email.php                           # Value Object - Email validado
│   └── PasswordHash.php                    # Value Object - Password hasheado
├── Infrastructure/
│   └── Entity/
│       └── RevokedToken.php                # Entidad Doctrine para tokens revocados
└── Repository/
    └── UserRepository.php                  # Repositorio Doctrine de usuarios

tests/Auth/
├── Integration/
│   ├── Auth.php                            # Test de endpoint de login
│   ├── Login.php                           # Test de flujo de login
│   ├── LoginToken.php                      # Test de generación de token
│   ├── Logout.php                          # Test de logout
│   └── LogoutRevocation.php                # Test de revocación de tokens
└── Unit/
    ├── Email.php                           # Test de Value Object Email
    ├── JwtTokenGenerator.php               # Test de generador JWT
    ├── PasswordHash.php                    # Test de PasswordHash
    └── User.php                            # Test de agregado User
```

#### 🛒 Cart - Módulo de Carrito

```
src/Cart/
├── routes.php                              # Definición de rutas del módulo
├── Application/
│   ├── Command/
│   │   ├── AddItemToCartCommand.php        # Command para añadir producto
│   │   ├── UpdateItemQuantityCommand.php   # Command para actualizar cantidad
│   │   ├── RemoveItemFromCartCommand.php   # Command para eliminar producto
│   │   └── CheckoutCartCommand.php         # Command para hacer checkout
│   ├── Handler/
│   │   ├── AddItemToCartHandler.php        # Handler de añadir producto
│   │   ├── UpdateItemQuantityHandler.php   # Handler de actualizar cantidad
│   │   ├── RemoveItemFromCartHandler.php   # Handler de eliminar producto
│   │   └── CheckoutCartHandler.php         # Handler de checkout
│   ├── Http/
│   │   └── Controller/
│   │       ├── AddItemController.php       # POST /api/cart/items
│   │       ├── UpdateItemController.php    # PUT/PATCH /api/cart/items/{id}
│   │       ├── RemoveItemController.php    # DELETE /api/cart/items/{id}
│   │       ├── GetCartController.php       # GET /api/cart/
│   │       ├── CheckoutCartController.php  # POST /api/cart/checkout
│   │       └── ResolveCartTrait.php        # Trait para resolver carrito
│   ├── Service/
│   │   ├── CartResolver.php                # Resuelve carrito de usuario
│   │   └── CartSerializer.php              # Serializa carrito a JSON
│   └── Exception/
│       ├── CartNotFoundException.php       # Excepción carrito no encontrado
│       ├── InvalidCartIdException.php      # Excepción ID inválido
│       └── UnauthorizedCartAccessException.php # Excepción acceso no autorizado
├── Domain/
│   ├── Cart.php                            # Agregado raíz del carrito
│   ├── CartItem.php                        # Entidad - Item del carrito
│   ├── CartId.php                          # Value Object - ID del carrito
│   ├── ProductId.php                       # Value Object - ID de producto
│   ├── Money.php                           # Value Object - Dinero
│   └── Port/
│       └── CartRepositoryInterface.php     # Puerto del repositorio
└── Infrastructure/
    └── DoctrineCartRepository.php          # Implementación Doctrine del repo

tests/Cart/
├── Integration/
│   ├── AddItemApi.php                      # Test API añadir item
│   ├── GetCartApi.php                      # Test API obtener carrito
│   ├── UpdateItemApi.php                   # Test API actualizar item
│   └── RemoveItemApi.php                   # Test API eliminar item
└── Unit/
    ├── CartHandlers.php                    # Test de handlers
    └── Money.php                           # Test de Value Object Money
```

#### 📋 Order - Módulo de Órdenes

```
src/Order/
├── Domain/
│   ├── Order.php                           # Agregado raíz de la orden
│   ├── OrderId.php                         # Value Object - ID de orden
│   ├── OrderStatus.php                     # Enum - Estados de orden
│   └── OrderRepositoryInterface.php        # Puerto del repositorio
├── Application/
│   ├── Command/
│   │   └── CancelOrderCommand.php          # Command para cancelar orden
│   ├── Query/
│   │   └── GetOrderQuery.php               # Query para obtener orden
│   └── Handler/
│       ├── CancelOrderHandler.php          # Handler de cancelación
│       └── GetOrderHandler.php             # Handler de consulta
└── Infrastructure/
    ├── DoctrineOrderRepository.php         # Implementación Doctrine
    └── Entity/
        ├── OrderEntity.php                 # Entidad Doctrine de orden
        └── OrderItemEntity.php             # Entidad Doctrine de item

tests/Order/
├── Api/
│   ├── CancelOrderApiTest.php              # Test API cancelar orden
│   └── GetOrderApiTest.php                 # Test API obtener orden
├── Integration/
│   ├── CancelOrderTest.php                 # Test integración cancelar
│   ├── CheckoutFlowTest.php                # Test integración checkout
│   ├── GetOrderTest.php                    # Test integración obtener
│   └── OrderStatusTransitionsTest.php      # Test transiciones de estado
└── Unit/
    └── OrderStatus.php                     # Test de Enum OrderStatus
```

#### 📦 Product - Módulo de Productos

```
src/Product/
├── routes.php                              # Definición de rutas del módulo
├── Application/
│   ├── Http/
│   │   └── Controller/
│   │       ├── CreateProductController.php # POST /api/products/
│   │       ├── ListProductsController.php  # GET /api/products/
│   │       ├── ShowProductController.php   # GET /api/products/{id}
│   │       ├── UpdateProductController.php # PUT/PATCH /api/products/{id}
│   │       └── DeleteProductController.php # DELETE /api/products/{id}
│   └── Service/
│       └── ProductService.php              # Servicio de orquestación
├── Domain/
│   ├── Product.php                         # Agregado raíz del producto
│   ├── ProductId.php                       # Value Object - ID de producto
│   ├── Money.php                           # Value Object - Precio
│   └── Repository/
│       └── ProductRepositoryInterface.php  # Puerto del repositorio
└── Infrastructure/
    └── DoctrineProductRepository.php       # Implementación Doctrine

tests/Product/
├── Integration/
│   ├── CreateProduct.php                   # Test crear producto
│   ├── ListProducts.php                    # Test listar productos
│   ├── ShowProduct.php                     # Test mostrar producto
│   ├── UpdateProduct.php                   # Test actualizar producto
│   └── DeleteProduct.php                   # Test eliminar producto
└── Unit/
    └── Money.php                           # Test Value Object Money
```

#### 💚 Health - Módulo de Health Check

```
src/Health/
├── routes.php                              # Definición de rutas
├── Application/
│   └── Http/
│       └── Controllers/
│           └── HealthCheckerController.php # GET /api/health
├── Domain/
│   └── (vacío - no hay lógica de dominio)
└── Infrastructure/
    └── (vacío - no hay infraestructura)

tests/Health/
└── Integration/
    └── Controllers/
        └── HealthCheckerController.php     # Test de health check
```

#### 📨 Message - Mensajes Asíncronos

```
src/Message/
└── Order/
    └── OrderProcessCartMessage.php         # Mensaje para procesar pago

src/MessageHandler/
└── OrderProcessCartMessageHandler.php      # Handler de procesamiento de pago
    ├─ Valida stock de productos
    ├─ Procesa pago (simulado)
    ├─ Actualiza estado de orden
    └─ Actualiza inventario
```

#### 🧪 Tests - Factories y Helpers

```
tests/
├── Factory/
│   ├── CartFactory.php                     # Factory de carritos para tests
│   ├── OrderFactory.php                    # Factory de órdenes para tests
│   ├── ProductFactory.php                  # Factory de productos para tests
│   └── UserFactory.php                     # Factory de usuarios para tests
└── TestCase/
    └── (helpers comunes para tests)
```

### Principios de Organización

#### Por Módulo (Vertical Slicing)
Cada módulo (`Auth`, `Cart`, `Order`, `Product`) es **autónomo** y contiene:
- **Domain**: Lógica de negocio pura, sin dependencias de framework
- **Application**: Casos de uso (CQRS), controllers, services
- **Infrastructure**: Implementaciones técnicas (Doctrine, etc.)

#### Arquitectura Hexagonal
- **Domain** = Núcleo (reglas de negocio)
- **Application** = Puertos de entrada (use cases, controllers)
- **Infrastructure** = Adaptadores (Doctrine, JWT, etc.)

#### Separación de Responsabilidades
- **Commands**: Operaciones de escritura (modifican estado)
- **Queries**: Operaciones de lectura (no modifican estado)
- **Handlers**: Ejecutan la lógica de los commands/queries
- **Controllers**: Adaptadores HTTP (transforman requests en commands/queries)
- **Repositories**: Abstracción de persistencia (puertos)

#### Tests Organizados por Tipo
- **Unit**: Tests de lógica pura de dominio (Value Objects, Entities, Enums)
- **Integration**: Tests de handlers, services y repositorios con BD real
- **Api**: Tests end-to-end de controllers con HTTP completo

---

## 🎯 Decisiones de Diseño y Trade-offs

### 1. SQLite para Tests / MySQL para Desarrollo
**Decisión**: Usar SQLite en tests para velocidad, MySQL en desarrollo para realismo.
**Trade-off**: Mayor velocidad de tests vs. posibles diferencias SQL.

### 2. Mensajería Síncrona en Tests
**Decisión**: Messenger usa transport `sync://` en tests.
**Trade-off**: Tests más rápidos y deterministas vs. no probar async real.

### 3. Pago Simulado
**Decisión**: Simular procesamiento de pago en `OrderProcessCartMessageHandler`.
**Trade-off**: Simplifica implementación vs. no integra gateway real.

### 4. JWT sin Refresh Token
**Decisión**: Implementar solo access token con revocación.
**Trade-off**: Más simple vs. usuario debe re-autenticarse al expirar.

### 5. Factories con Foundry
**Decisión**: Usar Zenstruck Foundry para factories de tests.
**Trade-off**: Abstracción potente vs. curva de aprendizaje.

---

## 🚀 Performance

### Optimizaciones Implementadas

1. **Eager Loading**: Relaciones Doctrine cargadas con `fetch="EAGER"` cuando es necesario
2. **Índices de BD**: IDs y campos de búsqueda indexados
3. **Procesamiento Asíncrono**: Pago procesado en background con Messenger
4. **Value Objects**: Inmutables y sin overhead de BD
5. **Caché de Doctrine**: Configurado para development y production

### Métricas de Ejemplo

```bash
# Tiempo de respuesta promedio (sin autenticación)
GET /api/health          ~5ms
GET /api/products/       ~20-50ms (dependiendo de límite)
GET /api/products/{id}   ~15-30ms

# Con autenticación
GET /api/cart/           ~30-60ms
POST /api/cart/items     ~50-100ms
POST /api/cart/checkout  ~100-150ms (+ procesamiento async)
```

---

## 📝 Notas Adicionales

### Próximos Pasos (Mejoras Futuras)

1. **Carritos Anónimos** 
   - Gestión de carritos sin autenticación usando cookies/session
   - Merge automático de carrito anónimo al hacer login
   - Persistencia temporal con expiración (7-30 días)
   - *Nota: El dominio ya está preparado con `userId` opcional*

2. **Gestión de IVA y Tipos Impositivos**
   - Separación de precio base + IVA en el modelo de datos
   - Soporte para diferentes tipos de IVA (21%, 10%, 4%, exento)
   - Desglose de IVA en facturas y reportes
   - Configuración de IVA por producto o categoría
   - *Nota: Actualmente todos los precios incluyen IVA del 21%*

3. **Integración con Gateway de Pago Real** (Stripe, PayPal)

4. **Refresh Tokens** para mejorar UX

5. **Rate Limiting** por endpoint

6. **Eventos de Dominio** más granulares

7. **Notifications** (Email, SMS) al completar orden

8. **Admin Panel** para gestión

9. **Webhooks** para integraciones externas

10. **Logs estructurados** con Monolog

11. **APM** (Application Performance Monitoring)

12. **CI/CD Pipeline** con GitHub Actions

### Git Workflow

El proyecto sigue **Git Flow** con:
- `main`: Rama principal estable
- `develop`: Rama de desarrollo
- `feature/*`: Ramas de funcionalidad
- `hotfix/*`: Correcciones urgentes

Commits siguiendo **Conventional Commits**:
```
feat: add checkout endpoint
fix: resolve cart item duplication
docs: update README with docker instructions
test: add integration tests for orders
```
## 📄 Licencia

Este proyecto es parte de una prueba técnica.

---


