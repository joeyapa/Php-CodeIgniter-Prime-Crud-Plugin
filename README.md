# Code Igniter and Prime UI CRUD Library Implementation.
Code Igniter SQL Driven CRUD Implementation Library that uses Prime UI as design front end. This library allows the basic features of primeui in a crud implementation format. Crud are common for most applications and this is one of the basic libraries that incorporates primeui with php. PrimeUI and better know for its java integration PrimeFaces. I made this library to do a few experiments on primeui and code igniter features. I also saw that there are no existing php plugins that support primeui. 

The crud library is sql driven, where-in you only need to include your select query and it will handle related crud (create, read, update and delete) functions. 

# History
August 2015: Initial draft. <br>
November 2015: Updated design, description and goals

# Requirement
To use the plugin the following libaries and changes are needed.

1. Import Jquery Core 1.6+
2. Import Jquery UI 1.11.4+
3. Import PrimeUI 2.0+
4. Modify the view and controller page

# Example
The following is the simplified usage of the crud library. The library requires only 3 lines of codes to make it work.

1. Controller Page. 
// load the crud primeui crud library
$this->load->library('crud');
// call the datatable function and passing a select statement
$data["crud_table"] = $this->crud->datatable("select {fieldnames} from {tablename}");

2. View Page
// display the generated crud table in the view page
<? echo $crud_table; ?>


