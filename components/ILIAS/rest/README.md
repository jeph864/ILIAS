# Webservices with Activities

## Abstract

Starts here: The aim of this project is to build a Webservices framework for ILIAS using activities.


## A way to A webservices framework: Conception

Currently (in late 2024), the Activities only exist as an idea in a readme and
some code to support the usage of them. But this is very thin, as there are no
actual Activities on the one hand but also no users of the Activities on the other
hand. We do not aim to implement all possible Activities at once, neither we expect
that all possible Activities will be implemented ever.

For this concept, we however implement a couple activities for courses and users. 
The next phase, after this general idea and the supporting code is approved by the
community, we aim to:

* implement a webservice interface over the activities
* pick one specific component (currently we think Study Programme) and implement
  some useful Activities for the Study Programme

This should showcase how the Activities are useful, but also should provide actual
value and lay the groundwork to quickly provide more value by adding additional
activities.

We also will be looking to replace the current SOAP implementation with an Activity
based implementation and provide the according actions as Activities.

## Design  
The Webservices API is divided into core Components and the "Consumers" of these components.
A consumer can be, among others: 
- A standard webservice protocol(SOAP, REST) 
- A custom webservice API
For the sake of simplicity this document will consider a JSON API and SOAP.

The core components act as a middle man between the domain logic which is handled by the Activities API and the actual 
consumers. These include:
- Authentication and Authorization components
- Specification and Validation components
- Error handling
- Routing

Each webservice implements a mapping to activities API and uses the core components to: 
- authenticate requests
- route requests to corresponding activities
- prepare responses
- validate requests and responses
- provide documentation/specification(service discovery??)


## API Life Cycle

### 1. Instantiation
- The cycle starts with an instance of a webservice.  This could be a RestApp or SOAPApp. During the instantation, the app registers all default services such as routing
middlewares, error handlers, routes repositories,...
- Next, you define routes using the application instance's routing methods: `get()` and `post()`. These methods register routes with the application's Router. Each method returns a `Route` instance, enabling you to chain additional methods to add middleware or assign a name to the route.
- Next you invoke the app's run method which takes the requests and routing contexts,  traverses the middleware stack inwardly and outwardsly and return a response.
### Middleware Stack
In webservices middleware acts as layers that wrap around your core application, 
forming a structured flow for handling requests and responses. Each middleware layer wraps around the previous ones, 
creating a **stack** that grows outward as more layers are added.  The last middleware layer you add will be the first one to process a request. 
When a request is made to the application, it flows through the middleware stack from the outermost layer inward. 
The request first enters the outermost middleware, then moves to the next layer, 
and so on, until it reaches the core application. 
Once the application processes the request and generates a response, the response travels back 
outward through the middleware stack in reverse order. It passes through each middleware layer again, starting from the innermost and ending with the outermost, 
before being returned as a finalized HTTP response to the client.
### 3. Activity Executor

The core part of the webservices API is the Activities API which handles the domain logic...

## Routing

The routing component in Webservices API manages the flow of HTTP requests and responses. It handles parsing incoming requests, resolving and dispatching routes, managing route definitions, and building arguments for route callbacks. It operates on a **request-response strategy**, ensuring that every route handler processes the request and returns a response object.

### Route Definitions and Creation

Routes define how specific HTTP requests are processed. You can define routes using methods like `get` and `post` for handling HTTP GET and POST requests. Additional methods can be defined using the `map` method. Each route consists of a URI pattern and a callback function that processes the request.

Here’s a simple example:

```php
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

$app->get('/hello/{name}', function (ServerRequestInterface $request, ResponseInterface $response, array $args) {
    $name = $args['name'];
    $response->getBody()->write("Hello, $name");

    return $response;
});
``` 
In this example: 
- /hello/{name} is the route pattern.
- {name} is a placeholder representing a dynamic URI segment.
- The callback processes the request and returns a response. 
### Placeholders in Routes
Placeholders are dynamic segments in the route pattern enclosed in `{}`. The application supports 3 main types of placeholders:

- **Simple placeholders**: Represent a single dynamic segment.  
  Example: `/course/{ref_id}/members`

- **Optional segments**: Enclose optional placeholders in `[]`.
```php
$app->get('/users[/{id}]', function ($req, $res, $args) {
return $res;
});
```

- **Unlimited segments**: Capture unlimited segments using :.*.
```php
$app->get('/news[/{params:.*}]', function ($req, $res, $args) {
    return $res;
});
```

### Route Middlewares  
Middleware can be attached to individual routes. Middleware allows you to add functionality like authentication, logging, or request modification. You attach middleware using the ->add() method.
```php
$app->get('/foo', function ($req, $res, $args) {
    return $res;
})->add(new SomeGoodMiddleware());
```
## Specification and Validation

### Authentication and Authorization

ILIAS already handles **authorization and password-based authentication**. However, as we move towards a **more flexible API**, we propose implementing **OAuth2-based authentication** alongside the existing mechanisms. This will allow **secure API interactions**, **client-based authentication**, (and **fine-grained access control**??????).

This implementation will support **multiple OAuth2 grant types** while enforcing **role-based access control (RBAC) and scope-based authorization**.

---

#### 1. Authentication Approach

##### 1.1 OAuth2 Grant Types

To accommodate different **clients and API access patterns**, one can  support multiple **OAuth2 grant types**:

| Grant Type | Use Case                             | Security Considerations                              |
|------------|--------------------------------------|------------------------------------------------------|
| **Authorization Code (with PKCE)** | Single Page Apps (SPAs), Mobile Apps | Prevents token interception without requiring client secrets |
| **Client Credentials** | Machine-to-Machine (API-to-API)      | Secure authentication for service-to-service interactions |
| **Resource Owner Password Credentials** | ???                                  | Avoid storing plaintext passwords in clients         |
| **Refresh Token** | Long-lived API sessions              | re-authentication without user intervention          |


##### 1.2 Token-Based Authentication

We will use **JWT (JSON Web Tokens)** for secure, stateless authentication:

- **Access Tokens** (`Bearer {token}`) will be **short-lived** (e.g., 15 minutes).
- **Refresh Tokens** allow obtaining new access tokens without requiring re-authentication.

#### 2. Authentication Middleware

To enforce authentication, we will introduce the following **middlewares**:

| Middleware | Function |
|------------|---------|
| **Token Middleware** | Extracts & validates access tokens (`Bearer {token}`) |
| **Client Authentication Middleware** | Validates `client_id` and `client_secret` in OAuth2 flows |
| **Role-Based Middleware** | Ensures users have the required roles |
---


## Dependencies:

- nikic/fastroute
- psr/http-server-middleware




