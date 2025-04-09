<?php
class EmailService {
    private $apiKey;
    private $fromEmail;
    
    public function __construct() {
        $this->apiKey = 're_BtCXgwta_AphCeiYV8M6cGxT1qsqqeYgp';
        $this->fromEmail = 'Gestion des Prêts <onboarding@resend.dev>';
    }
    
    public function sendEmail($to, $subject, $message, $useBcc = false) {
        try {
            $recipients = $useBcc ? explode(',', $to) : [$to];
            
            $data = array(
                'from' => $this->fromEmail,
                'to' => $recipients,
                'subject' => $subject,
                'html' => $message
            );

            $ch = curl_init('https://api.resend.com/emails');
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
            curl_setopt($ch, CURLOPT_HTTPHEADER, array(
                'Content-Type: application/json',
                'Authorization: Bearer ' . $this->apiKey
            ));

            $result = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($httpCode === 200) {
                return ['success' => true];
            } else {
                error_log("Erreur d'envoi d'email Resend : " . $result);
                return ['success' => false, 'error' => $result];
            }
            
        } catch (Exception $e) {
            error_log("Erreur d'envoi d'email : " . $e->getMessage());
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }
} 