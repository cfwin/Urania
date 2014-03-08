<?php
/*
Template file for page content

Author: Mathias Beke
Url: http://denbeke.be
Date: March 2014
*/
?>


<h2>Running Unit Tests</h2>


<div id="total-tests" <?php if($scenario->numberOfFailures != 0) echo 'class="failed"'; ?>>
	
	<p><?php echo $scenario->numberOfTest . ' tests in ' .  sizeof($scenario->tests) . ' test cases'; ?></p>
	
	<?php
	
	if($scenario->numberOfFailures == 0) {
		?>
		<p>All tests passed</p>
		<?php
	}
	else {
		?>
		<p><?php echo $scenario->numberOfFailures; ?> failures</p>
		<?php
	}
	
	?>
	
</div>



<?php

foreach ($scenario->tests as $case) {
	
	?>
	
	<h3><?php echo $case->name; ?></h3>
	
	<table>
		
		<tbody>
			
			
			<?php
			
			foreach ($case->sections as $section) {
			
			?>
			
			
			<tr <?php if(!$section->success) echo 'class="failed"'; ?>>
				
				<td>
					<?php echo $section->name; ?>
				</td>
				
				
				<td>
					
					<?php
					
					if($section->success) {
						echo 'OK';
					}
					else {
						echo 'ERROR';
						?>
						
						<ul class="failed-tests">
							
							<?php 
							
							foreach ($section->tests as $test) {
								if($test['result'] == false) {
									?>
									<li>
										line <?php echo $test['line']; ?>
									</li>
									<?php
								}
							}
							
							?>
							
						</ul>
						
						<?php
					}
					
					?>
					
				</td>
				
			</tr>
			
			
			
			<?php } ?>
			
			
		</tbody>
		
	</table>	
	
	<?php
	
}

?>