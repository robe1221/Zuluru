<div class="install">
	<h2><?php echo $title_for_layout; ?></h2>

<?php
if (isset($this->params['named']['execute'])) {
	if (!empty ($results)) {
		echo $this->Html->para(null, __('The following tables were updated:'));
		echo '<ul>';
		foreach ($results as $table => $result) {
			echo $this->Html->tag('li', "$table: $result");
		}
		echo '</ul>';
	}

	if ($failed) {
		echo $this->Html->para('error', __('Failed to complete the update!', true));
	} else {
		if (isset($config_file)) {
			echo $this->Html->para('error', sprintf (__('Failed to write to %s', true), $config_file));
			echo $this->Html->para('error', __('To ensure that future updates go smoothly, please update it manually with the following:', true));
			echo $this->Html->tag('pre', htmlentities($config_contents));
		}

		echo $this->Html->para(null, __('Delete the installation directory', true) . ' ' .
			$this->Html->tag('strong', '/app/plugins/install') . '.');
		echo $this->Html->link(__('Click here to delete installation files', true), array(
				'plugin' => 'install',
				'controller' => 'install',
				'action' => 'update',
				'delete' => 1,
		));
	}
} else if (isset($contents)) {
	echo $this->Html->para(null, sprintf(__('Found %d tables to update.', true), count($contents)));
	echo $this->Html->para(null, 'Ensure that the configured database user has CREATE and ALTER permissions on the database.');
	echo $this->Html->para(null, 'WARNING: You should perform a backup before proceeding. These updates are not typically reversible, and if anything goes wrong you will want a backup to restore from.');
	echo $this->Html->link(__('Click here to proceed with database updates.', true), array(
		'plugin' => 'install',
		'controller' => 'install',
		'action' => 'update',
		'execute' => 1,
	));
}

?>

</div>