<?php

namespace EntelisTeam\Lbaf\Core\Helper;
/**
 * @deprecated
 */
class html
{
    static function getTable($data)
    {
        $output = '';
        if (count($data)) {
            $keys = array_keys((array)reset($data));
            $output .= '<table border="1"><tr>';
            foreach ($keys as $key) {
                $output .= '<th>' . $key . '</th>';
            }
            $output .= '</tr>';
            foreach ($data as $item) {
                $output .= '<tr>';
                foreach ($item as $value) {
                    $output .= '<td>' . $value . '</td>';
                }
                $output .= '</tr>';
            }
            $output .= '</table>';
        }
        return $output;
    }
}