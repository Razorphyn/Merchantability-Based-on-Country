# Merchantability-Based-on-Country
 Limit the availabilty of a product based on customer country

 My aim is to achieve this result without any template change = heavy

## Project Status
Pre-Alpha

The basic functionalities has been addded:
 * Autonomous (based on IP) and manual(dropdown in header) country detection
 * Save product country option
 * Two rules: available or not availbale for the selected countries
 * Remove the main button for simple product (tested) in category and product view page
 * Block add to cart action for limited
 * Cart check on country change(not tested)
 * AJAX country change (incomplete/not tested)
 * Some others

**Efficiency**: car with elliptcal wheels

**Readability**: Ugh..

There is still a lot of work that has to be done
 **Compatible products**:
  * Simple - Tested
  * Configurable Product - Basically a "bruteforce" not tested
  * Grouped Product - To do
  * Bundled Product - To do
  * Related product - to do
  * cross-sells - to do
  * Up-sells - to be checked
  
  
### HOW TO INSTALL
 1. Go to **System > Tools > Compilations** and disable it
 2. Backup your store database and web directory
 3. Upload and  unzip the module in your magento root folder
 4. Completely clear the site cache

### HOW TO ENABLE
 Activate or deactivate the module
 System > Configuration > Razorphyn > Country Limiter Option > Enable : YES  or NO

 
### CONFIGURE THE MODULE
  Set to yes to configure the module
  1. System > Configuration > Razorphyn > Country Limiter Option >Enable setup : yes
  2. Razorphyn > Country Limiter Button Identifier
  3. Follow the links, you will be redirected to a product or category page where you have to click the add to cart button, once an succesfull alert pops up you can close the page and go on with the next link
 
### HOW TO USE
 1. Create a new product
 2. Complete all the required fields
 3. Press "Save and Continue" (or save and then reopen the product)
 4. A new section: "Country Limiter" will appear at the bottom of the left column

If you want to edit an already created product the section will be already there
