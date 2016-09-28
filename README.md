# neos-search
A Neos CMS package to add search functionality

## Installation
Add the package in your site package composer.json

`"require": {
     "obisconcept/neos-search": "~1.0.0"
 }`
 
 Add the subroute to the `Routes.yaml` of the Flow application
 
```
---
  name: 'ObisConcept.NeosSearch.SubRoutes'
  uriPattern: '<ObisConceptNeosSearchSubRoutes>'
  subRoutes:
    'ObisConceptNeosSearchSubRoutes':
      package: 'ObisConcept.NeosSearch'
```
