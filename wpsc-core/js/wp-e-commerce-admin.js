///////////////////////////////////////////////////////////////////////////////////////////////
// This section is used to create the globals that were originally defined in the 
// dynamic-js file pre 3.8.14.  Note that variables also also exist in the "wpsc_ajax" structure.

// iterate over the object and explicitly make each property a new global variable.  Because
// we are doing the operation in the global context the 'this' is the same as 'window' and 
// is the same functionally as do a 'var objectname;' statement. Creating 'global variables' 
// in this manner the new "variable" is enumerable and can be deleted.  
// 
// To add a new global property that can be referenced in the script see the hook 
// wpsc_javascript_localizations in wpsc-core/wpsc-functions.php
//
for (var a_name in wpsc_ajax) {
  if (wpsc_ajax.hasOwnProperty(a_name)) {
	  a_value = wpsc_ajax[a_name];
	  this[a_name] = a_value;
  }
}
//
///////////////////////////////////////////////////////////////////////////////////////////////
