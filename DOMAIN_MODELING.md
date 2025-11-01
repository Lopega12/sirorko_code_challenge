# 📊 Modelado del Dominio - Siroko Cart & Checkout API

Este documento describe el modelado del dominio siguiendo los principios de **Domain-Driven Design (DDD)** y **Arquitectura Hexagonal**.

---

## Diagrama de Agregados y Relaciones

```
┌─────────────────────────────────────────────────────────────┐
│                    CART (Agregado)                          │
├─────────────────────────────────────────────────────────────┤
│ - id: CartId                                                │
│ - userId: ?string                                           │
│ - items: CartItem[]                                         │
├─────────────────────────────────────────────────────────────┤
│ + addItem(CartItem)                                         │
│ + updateItemQuantity(ProductId, int)                        │
│ + removeItem(ProductId)                                     │
│ + total(): Money                                            │
│ + isEmpty(): bool                                           │
└─────────────────────────────────────────────────────────────┘
                    │
                    │ contiene
                    ▼
┌─────────────────────────────────────────────────────────────┐
│                  CART ITEM (Entidad)                        │
├─────────────────────────────────────────────────────────────┤
│ - productId: ProductId                                      │
│ - name: string                                              │
│ - price: Money                                              │
│ - quantity: int                                             │
├─────────────────────────────────────────────────────────────┤
│ + subtotal(): Money                                         │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                   ORDER (Agregado)                          │
├─────────────────────────────────────────────────────────────┤
│ - id: OrderId                                               │
│ - cartId: CartId                                            │
│ - items: CartItem[]                                         │
│ - total: Money                                              │
│ - status: OrderStatus                                       │
│ - paymentReference: ?string                                 │
├─────────────────────────────────────────────────────────────┤
│ + markAsProcessing()                                        │
│ + markAsPaid(string)                                        │
│ + markAsPaymentFailed()                                     │
│ + markAsCompleted()                                         │
│ + cancel()                                                  │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│              ORDER STATUS (Value Object - Enum)             │
├─────────────────────────────────────────────────────────────┤
│ PENDING         → Estado inicial tras checkout             │
│ PROCESSING      → Pago en proceso                           │
│ PAID            → Pago confirmado                           │
│ PAYMENT_FAILED  → Pago rechazado                            │
│ COMPLETED       → Orden completada                          │
│ CANCELLED       → Orden cancelada                           │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                  PRODUCT (Agregado)                         │
├─────────────────────────────────────────────────────────────┤
│ - id: ProductId                                             │
│ - sku: string                                               │
│ - name: string                                              │
│ - price: Money                                              │
│ - stock: int                                                │
│ - description: ?string                                      │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                   MONEY (Value Object)                      │
├─────────────────────────────────────────────────────────────┤
│ - cents: int                                                │
│ - currency: string                                          │
├─────────────────────────────────────────────────────────────┤
│ + fromFloat(float): Money                                   │
│ + fromCents(int): Money                                     │
│ + toFloat(): float                                          │
│ + toCents(): int                                            │
│ + add(Money): Money                                         │
│ + multiply(int): Money                                      │
│ + equals(Money): bool                                       │
└─────────────────────────────────────────────────────────────┘

┌─────────────────────────────────────────────────────────────┐
│                     USER (Agregado)                         │
├─────────────────────────────────────────────────────────────┤
│ - id: string                                                │
│ - email: Email                                              │
│ - password: PasswordHash                                    │
│ - roles: array                                              │
└─────────────────────────────────────────────────────────────┘
```

---

## Patrones DDD Implementados

### 1. **Aggregates (Agregados)**

Un **agregado** es un grupo de objetos de dominio que se tratan como una unidad para cambios de datos. Cada agregado tiene una raíz (Aggregate Root) que garantiza la consistencia.

#### Cart (Carrito)
- **Raíz del Agregado**: `Cart`
- **Responsabilidad**: Gestionar los items del carrito y calcular el total
- **Invariantes**:
  - No puede tener items con cantidad <= 0
  - No puede tener productos duplicados (se actualiza la cantidad)
  - El total se calcula automáticamente
- **Operaciones**:
  - `addItem()`: Añade un producto o incrementa su cantidad
  - `updateItemQuantity()`: Actualiza la cantidad de un producto
  - `removeItem()`: Elimina un producto del carrito
  - `total()`: Calcula el total del carrito

#### Order (Orden)
- **Raíz del Agregado**: `Order`
- **Responsabilidad**: Gestionar el ciclo de vida de una orden y sus transiciones de estado
- **Invariantes**:
  - Solo puede cancelarse en estados `PENDING` o `PAYMENT_FAILED`
  - Solo puede marcarse como `PROCESSING` desde `PENDING`
  - Solo puede marcarse como `PAID` o `PAYMENT_FAILED` desde `PROCESSING`
  - Solo puede completarse desde `PAID`
- **Operaciones**:
  - `markAsProcessing()`: Inicia el procesamiento del pago
  - `markAsPaid()`: Marca el pago como exitoso
  - `markAsPaymentFailed()`: Marca el pago como fallido
  - `markAsCompleted()`: Completa la orden
  - `cancel()`: Cancela la orden

#### Product (Producto)
- **Raíz del Agregado**: `Product`
- **Responsabilidad**: Representar un producto del catálogo
- **Invariantes**:
  - El precio debe ser mayor que 0
  - El stock no puede ser negativo
  - El SKU debe ser único

#### User (Usuario)
- **Raíz del Agregado**: `User`
- **Responsabilidad**: Representar un usuario del sistema
- **Invariantes**:
  - El email debe ser válido y único
  - La contraseña debe estar hasheada

---

### 2. **Entities (Entidades)**

Una **entidad** es un objeto de dominio que tiene identidad única y puede cambiar con el tiempo.

#### CartItem
- **Identidad**: `ProductId` (dentro del contexto del carrito)
- **Responsabilidad**: Representar un producto en el carrito con su cantidad
- **Atributos**:
  - `productId`: Identificador del producto
  - `name`: Nombre del producto (desnormalizado para performance)
  - `price`: Precio unitario al momento de agregarlo
  - `quantity`: Cantidad de unidades
- **Operaciones**:
  - `subtotal()`: Calcula el subtotal (precio × cantidad)

---

### 3. **Value Objects**

Un **Value Object** es un objeto inmutable que se define por sus atributos, no por su identidad.

#### Money (Dinero)
- **Propósito**: Representar valores monetarios de forma segura
- **Inmutabilidad**: No puede modificarse después de crearse
- **Operaciones**: `add()`, `multiply()`, `equals()`
- **Validación**: No permite valores negativos
- **Precisión**: Almacena valores en centavos para evitar errores de redondeo

```php
$price = Money::fromFloat(99.99); // 9999 centavos
$total = $price->multiply(3);     // 29997 centavos
echo $total->toFloat();            // 299.97
```

#### CartId / OrderId / ProductId
- **Propósito**: Identificadores tipados que previenen errores
- **Inmutabilidad**: No pueden modificarse
- **Generación**: UUID v4 para garantizar unicidad
- **Serialización**: Se pueden convertir a/desde string

```php
$cartId = CartId::generate();           // Genera nuevo UUID
$cartId = CartId::fromString($string);  // Desde string existente
echo (string) $cartId;                  // Convierte a string
```

#### Email
- **Propósito**: Email validado
- **Validación**: Formato de email válido
- **Inmutabilidad**: No puede modificarse

#### PasswordHash
- **Propósito**: Contraseña hasheada con algoritmo seguro
- **Seguridad**: Usa PASSWORD_BCRYPT
- **Verificación**: Método `verify()` para comparar

#### OrderStatus (Enum)
- **Propósito**: Estados posibles de una orden
- **Valores**: `PENDING`, `PROCESSING`, `PAID`, `PAYMENT_FAILED`, `COMPLETED`, `CANCELLED`
- **Métodos de consulta**:
  - `isPending()`, `isProcessing()`, `isPaid()`, etc.
  - `canBeCancelled()`: Valida si puede cancelarse
  - `canBeProcessed()`: Valida si puede procesarse
- **Descripciones**: Cada estado tiene una descripción en español

---

### 4. **Domain Events**

Los **Domain Events** representan algo que ocurrió en el dominio.

#### OrderProcessCartMessage
- **Propósito**: Notificar que una orden debe procesarse
- **Datos**: `orderId`
- **Handler**: `OrderProcessCartMessageHandler`
- **Flujo**:
  1. Se despacha tras crear la orden en el checkout
  2. El handler lo procesa de forma asíncrona
  3. Valida stock de productos
  4. Procesa el pago (simulado)
  5. Actualiza el estado de la orden
  6. Actualiza el inventario si el pago es exitoso

---

### 5. **Repositories (Ports)**

Los **repositorios** son puertos (interfaces) que abstraen la persistencia.

#### CartRepositoryInterface
```php
interface CartRepositoryInterface
{
    public function save(Cart $cart): void;
    public function find(CartId $id): ?Cart;
    public function findByUserId(string $userId): ?Cart;
}
```

#### OrderRepositoryInterface
```php
interface OrderRepositoryInterface
{
    public function save(Order $order): void;
    public function get(OrderId $id): ?Order;
}
```

#### ProductRepositoryInterface
```php
interface ProductRepositoryInterface
{
    public function save(Product $product): void;
    public function find(string $id): ?Product;
    public function findAll(int $limit, int $offset): array;
    public function delete(Product $product): void;
}
```

---

### 6. **Domain Services**

Los **servicios de dominio** contienen lógica que no pertenece a ningún agregado específico.

#### CartResolver
- **Propósito**: Resolver el carrito del usuario autenticado
- **Responsabilidad**: 
  - Obtener el usuario autenticado
  - Buscar o crear su carrito
  - Validar permisos de acceso

#### CartSerializer
- **Propósito**: Serializar agregados de dominio a JSON
- **Responsabilidad**:
  - Convertir `Cart` a array asociativo
  - Formatear `Money` para respuestas HTTP

---

## Flujo de Checkout y Procesamiento de Pago

### Diagrama de Secuencia

```
Usuario          API                Cart Handler        Order           Message Bus      Message Handler
  │               │                      │                │                  │                  │
  │── POST ──────>│                      │                │                  │                  │
  │  /checkout    │                      │                │                  │                  │
  │               │                      │                │                  │                  │
  │               │── CheckoutCommand ──>│                │                  │                  │
  │               │                      │                │                  │                  │
  │               │                      │── Validar ────>│                  │                  │
  │               │                      │   Cart         │                  │                  │
  │               │                      │                │                  │                  │
  │               │                      │── Crear Order ─┤                  │                  │
  │               │                      │   (PENDING)    │                  │                  │
  │               │                      │                │                  │                  │
  │               │                      │── Dispatch ───────────────────────>│                  │
  │               │                      │   Message      │                  │                  │
  │               │                      │                │                  │                  │
  │               │<── OrderId ──────────│                │                  │                  │
  │               │                      │                │                  │                  │
  │<── Response ──│                      │                │                  │                  │
  │   200 OK      │                      │                │                  │                  │
  │   order_id    │                      │                │                  │                  │
  │               │                      │                │                  │                  │
  │               │                      │                │                  │── Consume ──────>│
  │               │                      │                │                  │   Message        │
  │               │                      │                │                  │                  │
  │               │                      │                │<──── markAs ─────────────────────────│
  │               │                      │                │      Processing  │                  │
  │               │                      │                │                  │                  │
  │               │                      │                │                  │     Procesar     │
  │               │                      │                │                  │     Pago         │
  │               │                      │                │                  │     (simulado)   │
  │               │                      │                │                  │                  │
  │               │                      │                │<──── markAsPaid ─────────────────────│
  │               │                      │                │      (success)   │                  │
  │               │                      │                │      + ref       │                  │
  │               │                      │                │                  │                  │
  │               │                      │                │                  │    Actualizar    │
  │               │                      │                │                  │    Inventario    │
```

### Descripción del Flujo

#### 1. Checkout (Síncrono)
```
Usuario autenticado → POST /api/cart/checkout
  ↓
CheckoutCartHandler:
  ├─ Obtener carrito del usuario
  ├─ Validar que no esté vacío
  ├─ Crear Order (status: PENDING)
  │  └─ Copiar items del carrito
  ├─ Persistir orden
  ├─ Despachar OrderProcessCartMessage
  └─ Retornar OrderId
```

#### 2. Procesamiento de Pago (Asíncrono)
```
OrderProcessCartMessageHandler:
  ├─ Recibir mensaje con orderId
  ├─ Obtener Order
  ├─ markAsProcessing() → (status: PROCESSING)
  ├─ Validar stock de productos
  ├─ Procesar pago (simulado)
  │  ├─ ÉXITO:
  │  │  ├─ markAsPaid(paymentRef) → (status: PAID)
  │  │  └─ Actualizar inventario (reducir stock)
  │  └─ FALLO:
  │     └─ markAsPaymentFailed() → (status: PAYMENT_FAILED)
  └─ Persistir cambios
```

#### 3. Estados Finales Posibles

- **PAID**: Pago exitoso, orden lista para procesamiento logístico
- **PAYMENT_FAILED**: Pago rechazado, usuario puede reintentar
- **CANCELLED**: Usuario canceló la orden antes de procesar pago

---

## Transiciones de Estado de Order

### Diagrama de Estados

```
                           ┌─────────────┐
                           │   PENDING   │ ◄─── Estado inicial (checkout)
                           └──────┬──────┘
                                  │
                          markAsProcessing()
                                  │
                                  ▼
                           ┌─────────────┐
                      ┌───►│ PROCESSING  │
                      │    └──────┬──────┘
                      │           │
                      │           ├──────────── markAsPaid() ─────────┐
                      │           │                                    │
                      │           │                                    ▼
                      │           │                             ┌─────────┐
  cancel()            │           │                             │  PAID   │
  (solo desde         │           │                             └────┬────┘
   PENDING o          │           │                                  │
   PAYMENT_FAILED)    │           │                          markAsCompleted()
                      │           │                                  │
                      │           │                                  ▼
                      │           │                          ┌──────────────┐
                      │           │                          │  COMPLETED   │
                      │           │                          └──────────────┘
                      │           │
                      │           └─── markAsPaymentFailed() ───┐
                      │                                          │
                      │                                          ▼
                      │                                   ┌──────────────┐
                      └───────────────────────────────────┤PAYMENT_FAILED│
                                                          └──────────────┘
                                  │
                           cancel() permitido
                                  │
                                  ▼
                           ┌─────────────┐
                           │  CANCELLED  │
                           └─────────────┘
```

### Reglas de Negocio

1. **PENDING → PROCESSING**: Solo puede iniciarse el procesamiento desde estado pendiente
2. **PROCESSING → PAID**: El pago exitoso solo es posible durante el procesamiento
3. **PROCESSING → PAYMENT_FAILED**: El pago fallido solo ocurre durante el procesamiento
4. **PAID → COMPLETED**: La orden se completa después del pago exitoso
5. **Cancelación**: Solo permitida en estados `PENDING` o `PAYMENT_FAILED`

---

## Bounded Contexts

El sistema está organizado en contextos delimitados:

### 1. **Cart Context** (Contexto de Carrito)
- **Responsabilidad**: Gestión del carrito de compras
- **Agregados**: `Cart`, `CartItem`
- **Lenguaje Ubicuo**: carrito, item, producto, cantidad, total

### 2. **Order Context** (Contexto de Órdenes)
- **Responsabilidad**: Gestión del ciclo de vida de órdenes
- **Agregados**: `Order`
- **Lenguaje Ubicuo**: orden, checkout, pago, estado, procesamiento

### 3. **Product Context** (Contexto de Productos)
- **Responsabilidad**: Catálogo de productos
- **Agregados**: `Product`
- **Lenguaje Ubicuo**: producto, catálogo, SKU, stock, precio

### 4. **Auth Context** (Contexto de Autenticación)
- **Responsabilidad**: Autenticación y autorización
- **Agregados**: `User`
- **Lenguaje Ubicuo**: usuario, login, token, autenticación

---

## Consistencia Eventual

El sistema utiliza **consistencia eventual** en el procesamiento de pagos:

1. La orden se crea **inmediatamente** en estado `PENDING` (consistencia fuerte)
2. El procesamiento del pago ocurre **asincrónicamente** (consistencia eventual)
3. El usuario puede consultar el estado de la orden en cualquier momento
4. Las actualizaciones de inventario ocurren **después** del pago exitoso

**Ventajas**:
- ✅ Respuesta rápida al usuario (no espera el pago)
- ✅ Mejor experiencia de usuario
- ✅ Mayor escalabilidad (procesamiento en background)

**Consideraciones**:
- ⚠️ El usuario debe consultar el estado de la orden
- ⚠️ El inventario se actualiza con delay mínimo

---

## Invariantes del Dominio

### Cart
- ✅ No puede contener items con cantidad <= 0
- ✅ No puede contener productos duplicados
- ✅ El total se calcula automáticamente
- ✅ Un carrito vacío no puede hacer checkout

### Order
- ✅ No puede cambiar de estado a cualquier estado arbitrario
- ✅ Solo puede cancelarse en estados permitidos
- ✅ El total es inmutable después de la creación
- ✅ Los items son inmutables después de la creación

### Product
- ✅ El precio debe ser mayor que 0
- ✅ El stock no puede ser negativo
- ✅ El SKU debe ser único

### Money
- ✅ No permite valores negativos
- ✅ Operaciones aritméticas retornan nuevas instancias (inmutabilidad)
- ✅ Solo permite operaciones con la misma moneda

---

## Anti-corruption Layer

El proyecto implementa una **capa anti-corrupción** que protege el dominio:

- **Repositories**: Abstraen Doctrine del dominio
- **DTOs**: Transforman requests HTTP en Commands/Queries
- **Serializers**: Transforman agregados en respuestas HTTP
- **Value Objects**: Encapsulan validaciones y reglas de negocio

**Beneficio**: El dominio permanece puro y desacoplado del framework.

---

## Resumen

Este modelado del dominio implementa:

✅ **Aggregates** bien definidos con raíces claras  
✅ **Value Objects** inmutables para conceptos clave  
✅ **Entities** con identidad única  
✅ **Domain Events** para comunicación asíncrona  
✅ **Repositories** como puertos de persistencia  
✅ **Bounded Contexts** separados por responsabilidad  
✅ **Consistencia Eventual** para escalabilidad  
✅ **Invariantes** que protegen la integridad del dominio  
✅ **Anti-corruption Layer** que protege el dominio del framework  

El diseño permite:
- 🚀 **Escalabilidad**: Procesamiento asíncrono de pagos
- 🔒 **Integridad**: Invariantes garantizados por los agregados
- 🧪 **Testabilidad**: Dominio puro sin dependencias
- 📈 **Evolución**: Fácil agregar nuevas funcionalidades
- 🎯 **Claridad**: Lenguaje ubicuo compartido por el equipo

