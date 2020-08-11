/*
One configuration object for a resource
*/
/*
One Resource Association to another Resource
Example:  storagefacilityowners
Members: Map of foreignResources  (i.e. users), each containing keys about the
fieldnames needed to manage the data
*/
class ResourceAssociationItem{

  Name = "";
  ForeignResources = {};

  constructor(associativeCollectionName) {
    //console.log(`CTOR ${associativeCollectionName}`);
    this.Name = associativeCollectionName;
    this.ForeignResources = { };
  }
  /* Add a foreign resource for this association.  i.e. users
  */
  SetForeignAssociatedResource (  associativeCollectionName,
                                  foreignResourceName,
                                  primaryResourceIndexFieldName2,
                                  foreignResourceIndexFieldname2)
  {
    //console.log(`setting data ${foreignResourceName}`);
      this.ForeignResources[foreignResourceName] = {
        primaryResourceIndexFieldName : primaryResourceIndexFieldName2,
        foreignResourceIndexFieldname : foreignResourceIndexFieldname2
      };
  }
}

class ResourceConfigItem{

  Name = "";
  Fields = [];
  Associations = {};

  constructor(resourceName) {
    this.Name = resourceName;
  }

  //Add the db field keys (same as resource fields)
  AddFields(data){
    alert('adding fields - needs work ');

  }
  AddAssociation( associativeCollectionName,
                  foreignResourceName,
                  primaryResourceIndexFieldName,
                  foreignResourceIndexFieldName){

    //Create a new association item and push it onto the Associations collection

    //console.log('adding an association for ' + this.Name);
    if (!(associativeCollectionName in this.Associations)){

      var foreignResource = new ResourceAssociationItem( foreignResourceName );

      foreignResource.SetForeignAssociatedResource (  associativeCollectionName,
                                                      foreignResourceName,
                                                      primaryResourceIndexFieldName,
                                                      foreignResourceIndexFieldName);

      this.Associations[associativeCollectionName] = foreignResource;

    }
    //console.log("Resources after the add");
    //console.log(PWH_UIService.ResourceConfig);

  }

  GetAssociation (primaryResourceName, associativeCollectionName)
  {/*
    if  ( null != PWH_UIService.ResourceAssociations.primaryResourceName &&
          null != PWH_UIService.ResourceAssociations.ForeignResources[associativeCollectionName]
        )
    { return PWH_UIService.ResourceAssociations.ForeignResources[associativeCollectionName][foreignResourceName].primaryResourceIndexFieldName;

    }*/

  }


}
