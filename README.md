# Membership Web Application

An example membership plus CRUD site that showcases:

Web based App:(PHP/MySQL/ES6/Bootstrap)
- Membership Join, Login, Password Reset, Sessioning
- Configuration based site:  Comes with generic Login/Join/Logout/Profile

Data API Service:(PHP/MySQL)
- CRUD transactions


APIs:
  /User
  /Storagefacility

DESIGN is largely configuration driven and originally modeled after an example warehouse-storage solution.

WarehouseBaseType is the base class for resources, the configuration is
held in individual .json files in /config/resources/{Classname}.json

The top navigation holds static items and then adds the active resources

Notes on creating an object from config 

  // Create a new type, add one field per DB field.
  //
  // In the BaseType class, this json can be hydrated directly into an object
  //     array_merge used to created fields directly onto the $resource->DB_Fields
  // The constructor currently handles this by taking this type directly in
  //   the derived class (i.e. User, Storagefacility, ..), however most of
  //   the code is in the base class, activing mainly on configuration.
  //
  //  The first version takes this string each time an object is created.
  //    unfortunately, the design is bad because it is a waste to repeat such things.
  //  REFACTOR 1 - use singleton pattern to push properties and keys
  //        onto static class members, and keep data within the instance.
  //        Fieldnames are referenced when building forms, and referencing
  //        things like linked and associative collections (facilityowners, useritems, ...)
  //

  //  NOTE:
  //  A USER TYPE IS REQUIRED and is the Base of the system.
  //          fieldnames can change, but must have an ID, firstname, lastname
  //  profilename AND emailaddress are also required for now and are used to compare AUTH

  // activeResources - array of resource names that are actively used in the system
