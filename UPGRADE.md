# Upgrade Guide

This file provides notes on how to upgrade between versions.

## v0.6 to v0.7

### Config

The following new validation error keys need to be added to your `validation.php` config file:

```php
  /**
   * When the resource type of a related resource is not recognised.
   */
  V::RELATIONSHIP_UNKNOWN_TYPE => [
      Error::TITLE => 'Invalid Relationship',
      Error::DETAIL => "Resource type '{actual}' is not recognised.",
      Error::STATUS => 400,
  ]
```

### Store

If you have your own store implementation, you will need to add `Contracts\Store\StoreInterface::isType()` to your
implementation.

### Validation

If you have your own validation error factory implementation, you will need to add 
`Contracts\Validators\ValidatorErrorFactoryInterface::relationshipUnknownType()` to your implementation.

## v0.5 to v0.6

### Api

#### Api Factory

The `createApi` method signature on the `Contracts\Http\ApiFactoryInterface` has changed so that it only receives
the API namespace and the HTTP schema/host as the provided arguments. Configuration arrays can be injected into the
actual API factory instance using the `Http\ApiFactory::configure()` method.

#### Api Instance

The `ApiInterface` has been updated so that an API returns a HTTP factory, request interpreter and a store instance.
This allows applications with multiple APIs to define a store different stores and request settings on
a per-API basis. If you are implementing this interface anywhere you will need to update it
accordingly.

At the moment the `ApiFactory` included within this library injects that same request interpreter and
store into each API it builds, but our future intention is to make these injections configurable on
a per-API basis.

### Authorizers

The method signatures of `AuthorizerInterface::canUpdate()` and `canModifyRelationship()` have changed. `canUpdate`
now also received the resource provided by the client, and `canModifyRelationship` receives the relationship provided
by the client.

If you've implemented the interface yourself, you'll need to update your implementations. We've updated
this package's `AbstractAuthorizer` to reflect this change, however you will need to implement `canModifyRelationship`
yourself in any child classes.

`AbstractAuthorizer` now has an `ErrorRepositoryInterface` instance injected via its constructor. If you are extending
this class and overloading the constructor, you will need to update your constructor to ensure the parent constructor
is called.

### Exceptions

All internal package exceptions have been consolidated into a single class: `Exceptions\RuntimeException`. This 
means we have removed the following classes:

- `Exceptions\DocumentException`
- `Exceptions\HydratorException`
- `Exceptions\RepositoryException`
- `Exceptions\SchemaException`
- `Exceptions\StoreException`

### Objects

If you have implemented the following interfaces any where, you will need to make the changes described below:

#### DocumentInterface

`getData()` must now return an object, array or null as these are the return types defined in the JSON API spec.

You need to add a `getResources()` method that returns a `ResourceCollectionInterface` object if the data member is
an array.

#### ResourceIdentifierInterface

You need to add a `toString()` method to cast the identifier to a string. You must also add an `isSame()` method
that takes a resource identifier as its argument and returns `true` if this is logically the same as the identifier
on which the method is invoked.

### Requests

We've added a suite of request classes for processing incoming JSON API requests. The following interface and class
have been removed:

- `CloudCreativity\JsonApi\Contracts\Http\ContentNegotiatorInterface`
- `CloudCreativity\JsonApi\Http\ContentNegotiator`

You should use the `CloudCreativity\JsonApi\Contracts\Http\Requests\RequestFactoryInterface` instead. This package
supplied `CloudCreativity\JsonApi\Http\Requests\RequestFactoryInterface` as the default implementation.

### Responses

The `ErrorResponsesInterface` has been moved to the `Contracts\Http\Responses` namespace. The `ErrorResponse` object
has been moved to the `Http\Responses` namespace.

### Validation: Factory

If you are implementing the `ValidatorFactoryInterface`, you need to make the following changes:

- The argument for `resourceDocument` is now optional.
- The argument for `relationshipDocument()` is now optional.
- The `$expectedType` argument for `resource()` is now optional.
- You need to add the `relationship()` method and return a validator that validates either a has-one or a has-many
relationship. Use the new `RelationshipValidator` class if needed.

### Validation: Providers

The method signatures on `ValidatorProviderInterface` have changed and a new method `resource()` has been added. Refer
to the interface for details.

If you are implementing this interface you will need to update your method signatures.

Note that the interface now requires the resource type to be provided in the method signatures. This is so that the
method signatures match the information that would be known from a request (they can be obtained from the URL). This
does not stop you implementing a validator provider for each resource type, however it means that a validator
provider can be used across multiple resource types if needed.

### Validation: Relationships

The following changes are unlikely to affect most applications. Only applications that have extended validator
classes provided by this package or created their own implementations may be affected:

- `AcceptRelatedResourceInterface` may now return an `ErrorInterface` or `ErrorCollection` object instead of
a boolean. You'll need to update any validators that consume this interface to support these return types in
addition to a `boolean`.
- `ValidatorErrorFactoryInterface::relationshipNotAcceptable()` now takes a third argument (the custom
error) and must return an `ErrorCollection` instance.
- Internal methods within `AbstractRelationshipValidator`, `HasOneValidator` and `HasManyValidator` have been
re-organised to move all the validation logic into the abstract class. You will need to make changes to any validators
that extend these classes.

## v0.4 to v0.5

We decided to refactor the package, so there are a substantial amount of changes between these two versions. We
appreciate this will require some work for you to re-wire your implementations. However, the refactoring we've
done is based on us using the package in multiple production systems and represents a significant improvement
in the construction and extensibility of this package. We won't be refactoring again and we are planning on only
minor adjustments before hitting v1.0 of this package.

Note that some breaking changes were also made because the underlying `neomerx/json-api` package was upgraded from
`v0.6.6` to `v0.8.0`.
[You may need to refer to these notes.](https://github.com/neomerx/json-api/wiki/Upgrade-Notes)

Below is a brief summary of the main changes. If you're having problems working out how to upgrade, please submit an
issue with code examples and we'll help you out.

### Framework Integration

We have removed the abstracted framework integration interfaces classes that were in the following namespaces:
- `CloudCreativity\JsonApi\Contracts\Integration`
- `CloudCreativity\JsonApi\Integration`

These overcomplicated framework integration. Each integration package should write its own integration services
that integrate with how the specific framework delivers services. We see this as advantageous because you can
write the integration in the *style* of the framework you're integrating with.

### Error Handling

The underlying `neomerx/json-api` package made substantial changes to error handling - for instance it removed
Exception renderers and introduced throwing JSON API errors via a `JsonApiException` instance.

We've kept our approach of being able to construct errors from config arrays, as this is how we prefer to define
errors within an application instance. We've made some changes to our Error object -
`CloudCreativity\JsonApi\Document\Error` which implements
`CloudCreativity\JsonApi\Contracts\Document\MutableErrorInterface` to indicate that it is an error that has setters.

You can find an example of error configuration in the [validation error configuration file](config/validation.php)
You should use this file as the starting point for your error configuration in your application - it defines the
errors that the validators provided by this package will produce.

Your error configuration should be loaded into an instance of `CloudCreativity\JsonApi\Repositories\ErrorRepository`.
This is the service that is used to create errors from array config. Our approach is to implement this as a service
because this allows things such as translation to occur via this service if required.

### Validators

Our validator interfaces are now in the `CloudCreativity\JsonApi\Contracts\Validators` namespace, and default
instances are in the `CloudCreativity\JsonApi\Validators` namespace.

Previously we had a single validator interface. We now have a validator interface for each 'leaf' in the document
that is being validated. We found this is a better approach that allows each validator to be specific about which
part of the document it is validating.

In the validators namespace you'll find a `ValidatorFactory` class that implements the `ValidatorFactoryInterface`
interface. This factory can be used as a service to build the default validators that come with this package.

We've also introduced a `ValidatorProviderInterface`. The concept is that each JSON API resource type in your
application would have a validator provider instance that can provide your controller with the validators that
are specific for that resource type. We've moved to this approach because it allows us to unitize the
creation of resource specific validators, reducing the complexity of our controller code.

### Store

We've introduced a 'store' defined via the `CloudCreativity\JsonApi\Contracts\Store\StoreInterface`. This is a simple
interface to allow validators to check that a resource type/id combination exists.

A default store implementation is provided at `CloudCreativity\JsonApi\Store\Store`. To get this working, you need to
inject it with adapters - `CloudCreativity\JsonApi\Contracts\Store\AdapterInterface`. Adapters look up a type/id to
see if it exists and return the domain object that it refers to. Your adapters might be able to look up multiple JSON
API resource types, or you could have an adapter per type - or a combination of the two. E.g. in Laravel we have a
single adapter that can look up Eloquent models, and inject additional adapters that look up a few other resource
types that are not Eloquent models.

Your adapter tells the store whether it is handles a specified resource type via the `recognises()` method - refer
to the interface for details.
