<?php
	$this->load->view(
		'templates/FHC-Header',
		array(
			'title' => 'Alma',
			'jquery' => true,
			'jqueryui' => true,
			'bootstrap' => true,
			'fontawesome' => true,
			'sbadmintemplate' => true,
			'ajaxlib' => true,
			'navigationwidget' => true
		)
	);
?>

<body>
	<div id="wrapper">

		<?php echo $this->widgetlib->widget('NavigationWidget'); ?>

		<div id="page-wrapper">
			<div class="container-fluid">
				<div class="row">
					<div class="col-lg-12">
						<h3 class="page-header">Alma</h3>
					</div>
				</div>
				<div>
					<a href="<?php echo site_url().'/extensions/FHC-Core-Alma/Alma/export';?>">Export XML</a>
				</div>
			</div>
		</div>
	</div>
</body>

<?php $this->load->view('templates/FHC-Footer'); ?>
