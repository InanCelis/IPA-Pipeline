<?php
class XMLHelper {

    public static function render_taxonomy_values($terms, $xml_term) {
        if (!empty($terms) && is_array($terms)) {
            foreach ($terms as $term) {
                echo '<' . $xml_term . '>' . self::clean($term->name) . '</' . $xml_term . '>';
            }
        } 
        // Optional fallback logic:
        // else {
        //     if($xml_term == "type") {
        //         echo '<PropWOTypes>' . esc_url(get_permalink()) . '</PropWOTypes>';
        //     }
        // }
    }

    public static function remove_html($content) {
        $content = wp_strip_all_tags($content);
        $content = html_entity_decode($content, ENT_QUOTES | ENT_XML1, 'UTF-8');
        $content = preg_replace("/[\r\n]+/", "\n", $content);
        $content = nl2br(trim($content), false);
        return $content;
    }

    public static function clean_text($content) {
        // Remove all HTML tags including <br>, <p>, etc.
        $content = wp_strip_all_tags($content);

        // Decode HTML entities (e.g., &amp;, &#039;)
        $content = html_entity_decode($content, ENT_QUOTES | ENT_XML1, 'UTF-8');

        // Remove any remaining <br> tags (just in case)
        $content = preg_replace('/<br\s*\/?>/i', '', $content);

        // Normalize and clean up line breaks
        $content = preg_replace("/[\r\n]+/", " ", $content);

        // Trim leading/trailing whitespace
        return trim($content);
    }


    public static function clean($value) {
        $value = mb_convert_encoding($value, 'UTF-8', 'UTF-8');
        $value = preg_replace('/[\x{200B}-\x{200D}\x{FEFF}]/u', '', $value);
        return esc_html($value);
    }
}
