<?php

$neuron_size = 50;
$tiles_between_neurons = 2;
$draw_connection_weights = true;
$tiles_between_layers = 3;
$draw_grid = false;
$output_stats_image = true; // Output a second image with the ANN stats?

// Rotate view?
// default is "waterfall" inputs on top outputs on bottom
// rotate set to true is inputs on the left and outputs on the right
$rotate = false;

// Path to ANN's - change to your bot
$ann_name = "xor_float.net";
//$ann_name = "pathfinder_float.net";
//$ann_name = "dui.net";
//$ann_name = "ocr_float.net";



////////////////////////////////////////////////////////////////////////
// Don't Change Below this line
////////////////////////////////////////////////////////////////////////
$ann = fann_create_from_file(dirname(__FILE__) . DIRECTORY_SEPARATOR . $ann_name); //Load ANN

$num_​inputs = fann_get_num_input($ann); // int
$num_​layers = fann_get_num_layers($ann); // int
$num_​outputs = fann_get_num_output($ann); // int
$total_​neurons = fann_get_total_neurons($ann); // int
$total_​connections = fann_get_total_connections($ann); // int
$layers_array = fann_get_layer_array($ann); // array
$bias_array = fann_get_bias_array($ann); // array 
$connections_array = fann_get_connection_array($ann); // array of FANN connection objects

fann_destroy($ann);


// Array of FANN CONN OBJ's to Array of Keyed Nurons
// Use the connections to model the neurons.
// The $my_neurons array holds the neurons in a list with the key
// being the from_neuron. Additionally, this
// also creates a "connections" array on the neuron
// with a list of neurons that this neuron is connected 
// to with the value being the connection weight.
$my_neurons = array_fill(0, $total_​neurons, array());
foreach($connections_array as $connection){
	$my_neurons[$connection->from_neuron]['connections'][$connection->to_neuron] = $connection->weight;
}


// Figure out which neuron belongs on which layer 
// Assign it a type: ['input', 'output', 'hidden', 'bias']
$current_neuron = 0;
foreach($layers_array as $layer=>$layer_neuron_count){	

    // Detect Input, Output & Hidden Neurons 
	for($i = $current_neuron; $i < ($current_neuron + $layer_neuron_count); $i++){

		$my_neurons[$i]['layer'] = $layer;
		if($layer == 0){
			$my_neurons[$i]['type'] = 'input';	
		}
		elseif($layer == count($layers_array )-1){
			$my_neurons[$i]['type'] = 'output';	
		}
		else{
			$my_neurons[$i]['type'] = 'hidden';
		}
		
	}
	$current_neuron = $i;
	// Detect Bias Neurons 
	for($i = $current_neuron; $i < ($current_neuron + $bias_array[$layer]); $i++){
		$my_neurons[$i]['layer'] = $layer;
        $my_neurons[$i]['type'] = 'bias';	
	}
	$current_neuron = $i;
}
$current_neuron = NULL;
unset($current_neuron);



// Determine Neuron X,Y Position
$row = 1; // y
$col = 1; // x
foreach($my_neurons as $index=>&$neuron){
	
	if($neuron['layer'] > @$my_neurons[$index-1]['layer']){
		$row += $tiles_between_layers;
		$col = 1;
	}else{
		$col += $tiles_between_neurons;
	}
	
	$neuron['x'] = ($neuron_size * $col) - (($neuron_size / 2) );
	$neuron['y'] = ($neuron_size * $row) - (($neuron_size / 2) );
}



// Get complete_layer count = neurons + bias neurons
foreach($layers_array as $layer=>$layer_neuron_count){
	$complete_layers[$layer] = $layer_neuron_count + $bias_array[$layer];
}
$largest_layer = max($complete_layers); // Find the largest layer width


// Create a Blank Image
$image_width = ($neuron_size * ($largest_layer + ($tiles_between_neurons / 2))) * $tiles_between_neurons - ($neuron_size) + 1;
$image_height = ($neuron_size * $num_​layers) * $tiles_between_layers - ($neuron_size * ($tiles_between_layers - 1)) + 1;
$neural_network_image = imagecreatetruecolor($image_width, $image_height);

// Create Colors Array
$colors = array(
	'background'=>imagecolorallocate($neural_network_image, 153, 153, 153),
	'grid'=>imagecolorallocate($neural_network_image, 128, 128, 128),
	'neuron_stroke_color'=>imagecolorallocate($neural_network_image, 92, 92, 92),
	'input_neuron_color'=>imagecolorallocate($neural_network_image, 170, 255, 170),
	'hidden_neuron_color'=>imagecolorallocate($neural_network_image, 233, 175, 174),
	'output_neuron_color'=>imagecolorallocate($neural_network_image, 171, 204, 255),
	'bias_neuron_color'=>imagecolorallocate($neural_network_image, 255, 255, 0),
	'positive_connection_weight_color'=>imagecolorallocate($neural_network_image, 0, 255, 0),
	'negitive_connection_weight_color'=>imagecolorallocate($neural_network_image, 255, 0, 0),
	'dead_connection_weight_color'=>imagecolorallocate($neural_network_image, 0, 0, 0)
);


// Paint Background
imagefill($neural_network_image, 0, 0, $colors['background']);


// Draw Grid if $draw_grid is set to true 
if($draw_grid == true){
	$row = 0;
	$col = 0;
	foreach(range(0, $image_height - 1, 1) as $y){
		foreach(range(0, $image_width  - 1, 1) as $x){
			
			// paint grid
			if($row == $neuron_size || (($y % $neuron_size) == 0)){
				imagesetpixel($neural_network_image, $x, $y, $colors['grid']);
				$row = 0;
			}
			if($col == $neuron_size || (($x % $neuron_size) == 0)){
				imagesetpixel($neural_network_image, $x, $y, $colors['grid']);
				$col = 0; 
			}
			$col++;
		}
		$row++;
	}
}


// Draw Connections
foreach($my_neurons as $key=>&$neuron){
	
	if(array_key_exists('connections', $neuron)){
		foreach($neuron['connections'] as $connection=>$weight){
			
			// What color is the connection
			if($weight > 0.0){
				$color = $colors['positive_connection_weight_color'];
			}
			elseif($weight < 0.0){
				$color = $colors['negitive_connection_weight_color'];
			}
			else{
				$color = $colors['dead_connection_weight_color'];
			}
			
			// Set connection thickness
			if($draw_connection_weights == true){
				$thickness = 1 + (abs($weight) * 2);
				
				if($thickness > 32){$thickness = 32;}
			    imagesetthickness ($neural_network_image , $thickness);

			}
			else{
				imagesetthickness ($neural_network_image , 1);
			}
			
			// Draw connection
			imageline ($neural_network_image , $neuron['x'], $neuron['y'], $my_neurons[$connection]['x'], $my_neurons[$connection]['y'], $color);
		}	
	}

}


imagesetthickness ($neural_network_image , 2); // Reset line brush thickness

// Draw Neurons
foreach($my_neurons as $key=>&$neuron){
	
	if($neuron['type'] == 'input'){
		$color = $colors['input_neuron_color'];
	}
	elseif($neuron['type'] == 'hidden'){
		$color = $colors['hidden_neuron_color'];
	}
	elseif($neuron['type'] == 'output'){
		$color = $colors['output_neuron_color'];
	}
	elseif($neuron['type'] == 'bias'){
		$color = $colors['bias_neuron_color'];
	}
	
	imagefilledellipse($neural_network_image, $neuron['x'], $neuron['y'], $neuron_size, $neuron_size, $color);
	imagearc ($neural_network_image, $neuron['x'], $neuron['y'], $neuron_size+1, $neuron_size+1, 0, 360, $colors['neuron_stroke_color']);
}

// Rotate if you insist on looking at the network wrong! :-P
if($rotate == true){
    $neural_network_image = imagerotate($neural_network_image, 90, 0);
}


// Output the image.
imagepng($neural_network_image, "$ann_name.png");
imagedestroy($neural_network_image);




if($output_stats_image == true){
	// Create the image
	$neural_network_stats_image = imagecreatetruecolor(250, 400);
	
	// Create Colors Array
	$colors = array(
		'background'=>imagecolorallocate($neural_network_stats_image, 153, 153, 153),
		'inputs_text_color'=>imagecolorallocate($neural_network_stats_image, 170, 255, 170),
		'hidden_text_color'=>imagecolorallocate($neural_network_stats_image, 233, 175, 174),
		'outputs_text_color'=>imagecolorallocate($neural_network_stats_image, 171, 204, 255),
		'bias_text_color'=>imagecolorallocate($neural_network_stats_image, 255, 255, 0),
		'layers_text_color'=>imagecolorallocate($neural_network_stats_image, 0, 128, 128),
		'connections_text_color'=>imagecolorallocate($neural_network_stats_image, 128, 64, 0),
	);

	// Paint Background
	imagefill($neural_network_stats_image, 0, 0, $colors['background']);




	$font = dirname(__FILE__) . DIRECTORY_SEPARATOR . 'Pacifico'. DIRECTORY_SEPARATOR . 'Pacifico-Regular.ttf';

    $size = 25;
    $angle = 0.00;
    $x = 25;
    $y = 50;
	$increment = $y + 10;

	imagettftext($neural_network_stats_image, $size, $angle, $x, $y, $colors['inputs_text_color'], $font, $num_​inputs . ' Inputs');
	$y += $increment;
	imagettftext($neural_network_stats_image, $size, $angle, $x, $y, $colors['hidden_text_color'], $font, $total_​neurons - ($num_​inputs + $num_​outputs + array_sum($bias_array)) . ' Hidden');
	$y += $increment;
    imagettftext($neural_network_stats_image, $size, $angle, $x, $y, $colors['outputs_text_color'], $font, $num_​outputs . ' Outputs');
	$y += $increment;
	imagettftext($neural_network_stats_image, $size, $angle, $x, $y, $colors['bias_text_color'], $font, array_sum($bias_array) . ' Bias');
    $y += $increment;
	imagettftext($neural_network_stats_image, $size, $angle, $x, $y, $colors['layers_text_color'], $font, count($layers_array) . ' Layers');
    $y += $increment;
	imagettftext($neural_network_stats_image, $size, $angle, $x, $y, $colors['connections_text_color'], $font, count($connections_array) . ' Connections');
    $y += $increment;
	
	imagepng($neural_network_stats_image, "$ann_name.stats.png");
	imagedestroy($neural_network_stats_image);
}