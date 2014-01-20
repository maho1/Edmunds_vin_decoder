<?php
// Writen by Larry Bislew aka bislewl

// Add your Edmunds API key below
// or if you don't have one go here http://edmunds.mashery.com/member/register

$vin = trim($_POST['vin']);
$edmunds_api_key = 'API_KEY_HERE_IT_MUST_HAVE_ONE';


function edmunds_api_req($url,$postData = array('')){
// Setup cURL
$ch = curl_init($url);
curl_setopt_array($ch, array(
    CURLOPT_POST => TRUE,
    CURLOPT_RETURNTRANSFER => TRUE,
    CURLOPT_POSTFIELDS => json_encode($postData)
));
$response = curl_exec($ch);
if($response === FALSE){
    die(curl_error($ch));
}
$responseData = json_decode($response, TRUE);
curl_close($ch);
sleep(2);

return $responseData;
}

if(isset($_POST['vin'])){
$vin_url = 'https://api.edmunds.com/api/vehicle/v2/vins/'.$vin.'?fmt=json&api_key='.$edmunds_api_key;
$vin_response = edmunds_api_req($vin_url);

$styles_id = $vin_response['years'][0]['styles'][0]['id'];
$style_details_url = 'https://api.edmunds.com/api/vehicle/v2/styles/'.$styles_id.'?view=full&fmt=json&api_key='.$edmunds_api_key;
$style_details_response = edmunds_api_req($style_details_url);

$photos_url = 'https://api.edmunds.com/v1/api/vehiclephoto/service/findphotosbystyleid?styleId='.$styles_id.'&fmt=json&api_key='.$edmunds_api_key;
$photos_response = edmunds_api_req($photos_url);
$photo_array = array();
$photo_alt = '';
foreach ($photos_response as $photo_group){
    if($photo_group['shotTypeAbbreviation'] == 'FQ'){
        $photo_array = $photo_group['photoSrcs'];
        $photo_alt = $photo_group['captionTranscript'];
    }
}
  
$colors_url = 'https://api.edmunds.com/api/vehicle/v2/styles/'.$styles_id.'/colors?fmt=json&api_key='.$edmunds_api_key;
$colors_response = edmunds_api_req($colors_url);
$exterior_colors = array();
$interior_colors = array();

foreach($colors_response['colors'] as $color_parent){
    switch ($color_parent['category']){
        case "Exterior":
            $exterior_colors[] = $color_parent;
            break;
        case "Interior":
            $interior_colors[] = $color_parent;
            break;
        default :
            break;
    }
}

$equipment_url = 'https://api.edmunds.com/api/vehicle/v2/styles/'.$styles_id.'/equipment?fmt=json&api_key='.$edmunds_api_key;
$equipment_response = edmunds_api_req($equipment_url);
}

?>

<html>
    <head></head>
    <body>
        <h1>Vin Decoder</h1>
        <form name="input" method="post">
            <input type="text" name="vin" value="<?=$vin?>">
            <input type="submit" value="Submit">
        </form>    
        <?php
        if(!isset($_POST['vin'])){
            die();
        }
        ?>
        <table>
            <tr>
                <td>
                    <table>
                        <tr><td><b>Year:</b></td><td><?=$vin_response['years'][0]['year']?></td></tr>
                        <tr><td><b>Make:</b></td><td><?=$vin_response['make']['name']?></td></tr>
                        <tr><td><b>Model:</b></td><td><?=$vin_response['model']['name']?></td></tr>
                        <tr><td><b>Style:</b></td><td><?=$vin_response['years'][0]['styles'][0]['name']?></td></tr>
                        <tr><td><b>Engine:</b></td><td><?=$style_details_response['engine']['manufacturerEngineCode']?></td></tr>
                        <tr><td><b>Transmission:</b></td><td><?=$style_details_response['transmission']['name']?></td></tr>
                        <tr><td><b>Drive Wheels:</b></td><td><?=$style_details_response['drivenWheels']?></td></tr>
                        <tr><td><b>Doors:</b></td><td><?=$style_details_response['numOfDoors']?></td></tr>
                        <tr><td><b>City</b><br/><?=$vin_response['MPG']['city']?></td><td><b>Highway</b><br/><?=$vin_response['MPG']['highway']?></td></tr>
                    </table>
                </td>
                <td>
                    <img src="//media.ed.edmunds-media.com<?=$photo_array[0]?>" alt="<?=$photos_response[0]['captionTranscript']?>" width="425px">
                </td>
                <td>
                    <h2>Values</h2><br/>
                    <b>Retail:</b> <?=$vin_response['price']['usedTmvRetail']?><br/>
                    <b>Private Party:</b> <?=$vin_response['price']['usedPrivateParty']?><br/>
                    <b>Trade:</b> <?=$vin_response['price']['usedTradeIn']?><br/>
                </td>    
            </tr>
        </table>
        <br/>
        <table><tr><td>
        <?php
        foreach ($style_details_response['options'] as $options_parent){
            echo '<ul>';
            echo '<h2>'.$options_parent['category'].'</h2>';
            foreach ($options_parent['options'] as $options){
                echo '<li>';
                echo '<b>'.$options['manufactureOptionName'].'</b> '.$options['description'].' MSRP $'.$options['price']['baseMSRP'];
            }
            echo '</ul><br/>';
        }
        ?>
                </td>
                <td style="width: 50%; vertical-align: top">
                <h2>Colors:</h2>
                <table>
                    <tr>
                        <td>
                            <h2>Exterior</h2>
                            <table>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Color</th>
                                </tr>
                                <?php
                                        foreach ($exterior_colors as $ext_color){
                                            echo '<tr><td><b>'.$ext_color['manufactureOptionCode'].'</b></td>';
                                            echo '<td>'.$ext_color['manufactureOptionName'].'</td>';
                                            echo '<td style="background:#'.$ext_color['colorChips']['primary']['hex'].';"></td></tr>';
                                        }
                                ?>
                            </table>
                        </td>
                        <td style="padding-left: 50px;">
                            <h2>Interior</h2><br/>
                            <table>
                                <tr>
                                    <th>Code</th>
                                    <th>Name</th>
                                    <th>Color</th>
                                    <th>Fabric</th>
                                </tr>
                                <?php
                                        foreach ($interior_colors as $int_color){
                                            echo '<tr><td><b>'.$int_color['manufactureOptionCode'].'</b></td>';
                                            echo '<td>'.$int_color['manufactureOptionName'].'</td>';
                                            echo '<td style="background:#'.$int_color['colorChips']['primary']['hex'].';"></td>';
                                            echo '<td>';
                                            foreach($int_color['fabricTypes'] as $fabric){
                                                echo $fabric['value'].'<br/>';
                                            }
                                            echo '</td></tr>';
                                        }
                                ?>
                            </table>
                        </td>
                    </tr>
                </table>
                    
                </td>
        </table>
        <table>
            <?php
            foreach ($equipment_response['equipment'] as $equipment){
                echo '<ul>'.$equipment['name'].' ('.$equipment['equipmentType'].') - '.$equipment['availability'];
                if(!empty($equipment['attributes'])){
                foreach ($equipment['attributes'] as $equip_attribute){
                    echo '<li><b>'.$equip_attribute['name'].':</b> '.$equip_attribute['value'];
                }}
                echo '</ul>';
            }
            
            ?>
        </table>
    </body>
    
</html>