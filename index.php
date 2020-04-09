<?php 
/**
 * Plugin Name: Vietcombank Tỷ Giá
 * Plugin URI: https://tienbac.edu.vn/plguins/vietcombank-ty-gia-wordpress-plugin
 * Description: Chèn bảng tỷ giả cập nhật từ ngân hàng Vietcombank, có hỗ trợ shortcode
 * Version: 0.1
 * Text Domain: vietcombank-ty-gia
 * Author: TienBac.Edu.vn
 * Author URI: https://tienbac.edu.vn
 */

class VCB_ExRate {
    static $_vcb_cache_name = 'ty_gia_cache.txt';

    private static function get_exrate() {
        $arrContextOptions=array(
            "ssl" => array(
                 "verify_peer" => false,
                 "verify_peer_name" => false,
            ),
        );  
        $vietcombank = file_get_contents(
            "https://portal.vietcombank.com.vn/Usercontrols/TVPortal.TyGia/pXML.aspx", 
            false,
            stream_context_create($arrContextOptions)
        );

        if ($vietcombank)
            static::put_cache($vietcombank);
        else
            return null;
        
        return simplexml_load_string($vietcombank);
    }


    private static function get_cache() {
        if (!file_exists(__DIR__.DIRECTORY_SEPARATOR.static::$_vcb_cache_name))
            return null;

        $cache = file_get_contents(__DIR__.DIRECTORY_SEPARATOR.static::$_vcb_cache_name);
        return simplexml_load_string($cache);
        
    }

    private static function put_cache($data) {
        file_put_contents(__DIR__.DIRECTORY_SEPARATOR.static::$_vcb_cache_name, $data);
    }

    public static function run() {
        $data = $old_data = static::get_cache();
        $new_data = false;
        if (empty($data)) {
            $data = static::get_exrate();
            if (!$data) return null;
            $new_data = true;
        }

        $DateTime = $data->DateTime;
        $DateTime = date_create_from_format('m/d/Y h:i:s A', $DateTime, new DateTimeZone('Asia/Ho_Chi_Minh'));

        if (!$new_data || !$DateTime) {
            $data = static::get_exrate();
            if (!$data) {
                if ($old_data) $data = $old_data;
                else return null;
            }
        }

        $Now = new DateTime('now', new DateTimeZone('Asia/Ho_Chi_Minh'));
        $diff = 0;
        if ($DateTime)
            $diff = $Now->getTimestamp() - $DateTime->getTimestamp();

        if (!$new_data && $diff > 1800) {
            $data = static::get_exrate();
            
            if (!$data) {
                if ($old_data) $data = $old_data;
                else return null;
            }
        }

        $content = '';
        $content .= '<table class="vcb_tygia_ngoai_te">';
            $content .= '<thead>';
                $content .= '<tr>';
                    $content .= '<th colspan="2">Ngoại tệ</th>';
                    $content .= '<th colspan="2">Mua</th>';
                    $content .= '<th rowspan="2">Bán</th>';
                $content .= '</tr>';
                $content .= '<tr>';
                    $content .= '<th>Tên ngoại tệ</th>';
                    $content .= '<th>Mã</th>';
                    $content .= '<th>Tiền mặt</th>';
                    $content .= '<th>Chuyển khoản</th>';
                $content .= '</tr>';
            $content .= '</thead>';
            $content .= '<tbody>';
                foreach ($data->Exrate as $index => $Exrate):
                    $content .= '<tr>';
                        $content .= '<td>'.$Exrate['CurrencyName'].'</td>';
                        $content .= '<td>'.$Exrate['CurrencyCode'].'</td>';
                        $content .= '<td>'.$Exrate['Buy'].'</td>';
                        $content .= '<td>'.$Exrate['Transfer'].'</td>';
                        $content .= '<td>'.$Exrate['Sell'].'</td>';
                    $content .= '</tr>';
                endforeach;
            $content .= '</tbody>';
            $content .= '<tfoot>';
                $content .= '<tr>';
                    $content .= '<td colspan="5"><?php if ($DateTime):?><em>Thông tin cập nhật lúc: '.$DateTime->format('d/m/Y H:i:s').'</em><?php endif; ?></td>';
                $content .= '</tr>';
                $content .= '<tr><td colspan="5"><em>Nguồn: '. $data->Source .'</em></td></tr>';
            $content .= '</tfoot>';
        $content .= '</table>';

        return $content;
    }
}

// VCB_ExRate::run();
add_shortcode('vcb_exrate', ['VCB_ExRate', 'run']);    

?>
