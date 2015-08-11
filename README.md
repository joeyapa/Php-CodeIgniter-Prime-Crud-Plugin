# Code Igniter and Prime UI CRUD Library Implementation.
Code Igniter SQL Driven CRUD Implementation Library that uses Prime UI as design front end.

# Requirement
1. Import Jquery Core 1.6+
2. Import Jquery UI 1.11.4+
3. Import PrimeUI 2.0+

# Usage
1. Controller Page
$this->load->library('crud');
$data["crud_table"] = $this->crud->datatable("select vehicle_id, vehicle_no, name, description from t020_vehicle");

2. View Page. 
<link rel="stylesheet" type="text/css" href="<?=base_url('resources/jquery-ui/jquery-ui.min.css')?>" />
<link rel="stylesheet" type="text/css" href="<?=base_url('resources/font-awesome/css/font-awesome.min.css')?>" />
<link rel="stylesheet" type="text/css" href="<?=base_url('resources/primeui/primeui-2.0-min.css')?>" />
<link rel="stylesheet" type="text/css" href="<?=base_url('resources/primeui/themes/<theme>/theme.css')?>" />
<script type="text/javascript" src="<?=base_url('resources/jquery/jquery.min.js')?>"></script>
<script type="text/javascript" src="<?=base_url('resources/jquery-ui/jquery-ui.min.js')?>"></script>
<script type="text/javascript" src="<?=base_url('resources/primeui/primeui-2.0-min.js')?>"></script>

<? echo $crud_table; ?>


