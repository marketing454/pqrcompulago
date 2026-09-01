<?php
/**
 * WhatsAppService - Integración con Meta Cloud API
 */

class WhatsAppService {
    
    // Estos valores deben ser configurados en config.php
    private static $token = WHATSAPP_TOKEN;
    private static $phone_id = WHATSAPP_PHONE_ID;
    private static $api_version = WHATSAPP_API_VERSION;

    /**
     * Enviar mensaje de plantilla cuando se crea un PQR
     */
    public static function sendTicketConfirmation($phone, $client_name, $radicado) {
        if (!self::isValid()) return false;

        $payload = [
            "messaging_product" => "whatsapp",
            "to" => self::formatPhone($phone),
            "type" => "template",
            "template" => [
                "name" => "pqr_confirmation_new", // Nombre de la plantilla aprobada en Meta
                "language" => ["code" => "es"],
                "components" => [
                    [
                        "type" => "body",
                        "parameters" => [
                            ["type" => "text", "text" => $client_name],
                            ["type" => "text", "text" => $radicado]
                        ]
                    ]
                ]
            ]
        ];

        return self::callAPI($payload);
    }

    /**
     * Enviar mensaje de texto cuando hay una actualización
     */
    public static function sendTicketReplyNotification($phone, $radicado) {
        if (!self::isValid()) return false;

        $payload = [
            "messaging_product" => "whatsapp",
            "recipient_type" => "individual",
            "to" => self::formatPhone($phone),
            "type" => "text",
            "text" => [
                "preview_url" => false,
                "body" => "🔔 *Compulago Informa:* Hemos respondido a tu solicitud con radicado *$radicado*. Por favor revisa tu correo electrónico para ver los detalles. ¡Gracias!"
            ]
        ];

        return self::callAPI($payload);
    }

    private static function formatPhone($phone) {
        // Asegurar formato internacional (ej: 573001234567)
        $phone = preg_replace('/[^0-9]/', '', $phone);
        if (strlen($phone) == 10) $phone = "57" . $phone;
        return $phone;
    }

    private static function isValid() {
        return !empty(self::$token) && !empty(self::$phone_id);
    }

    private static function callAPI($payload) {
        $url = "https://graph.facebook.com/" . self::$api_version . "/" . self::$phone_id . "/messages";
        
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            "Authorization: Bearer " . self::$token,
            "Content-Type: application/json"
        ]);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($payload));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($ch, CURLOPT_TIMEOUT, 10);
        
        $response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curl_error !== '') {
            error_log('WhatsApp API request failed: ' . ($curl_error ?: 'unknown error'));
            return false;
        }

        if ($http_code < 200 || $http_code >= 300) {
            error_log('WhatsApp API returned HTTP ' . $http_code . '.');
        }

        return ($http_code >= 200 && $http_code < 300);
    }
}
?>
