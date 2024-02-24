<?php

namespace NW\WebService\References\Operations\Notification;

/**
 * Represents an operation related to returns (TsReturnOperation).
 */
class TsReturnOperation extends ReferencesOperation
{
    public const TYPE_NEW = 1;
    public const TYPE_CHANGE = 2;

    /**
     * Performs the return operation.
     *
     * @return array The result of the operation.
     * @throws \Exception When an error occurs.
     */
    public function doOperation(): array
    {
        $data             = (array)$this->getRequest('data');
        $resellerId       = $data['resellerId'];
        $notificationType = (int)$data['notificationType'];
        $result           = [
            'notificationEmployeeByEmail' => false,
            'notificationClientByEmail'   => false,
            'notificationClientBySms'     => [
                'isSent'  => false,
                'message' => '',
            ],
        ];

        if (empty($resellerId)) {
            throw new \Exception('Empty resellerId', 400);
        }

        if (empty($notificationType)) {
            throw new \Exception('Empty notificationType', 400);
        }

        $reseller = Seller::getById((int)$resellerId);
        if ($reseller === null) {
            throw new \Exception('Seller not found!', 400);
        }

        $client = Contractor::getById((int)$data['clientId']);
        if ($client === null || $client->type !== Contractor::TYPE_CUSTOMER || $client->seller->id !== $resellerId) {
            throw new \Exception('Client not found or invalid type!', 400);
        }

        $cFullName = $client->getFullName();
        if (empty($cFullName)) {
            $cFullName = $client->name;
        }

        $cr = Employee::getById((int)$data['creatorId']);
        if ($cr === null) {
            throw new \Exception('Creator not found!', 400);
        }

        $et = Employee::getById((int)$data['expertId']);
        if ($et === null) {
            throw new \Exception('Expert not found!', 400);
        }

        $differences = '';
        if ($notificationType === self::TYPE_NEW) {
            $differences = __('NewPositionAdded', null, $resellerId);
        } elseif ($notificationType === self::TYPE_CHANGE && ! empty($data['differences'])) {
            $differences = __('PositionStatusHasChanged', [
                'FROM' => Status::getName((int)$data['differences']['from']),
                'TO'   => Status::getName((int)$data['differences']['to']),
            ], $resellerId);
        }

        $templateData = [
            'COMPLAINT_ID'       => (int)$data['complaintId'],
            'COMPLAINT_NUMBER'   => (string)$data['complaintNumber'],
            'CREATOR_ID'         => (int)$data['creatorId'],
            'CREATOR_NAME'       => $cr->getFullName(),
            'EXPERT_ID'          => (int)$data['expertId'],
            'EXPERT_NAME'        => $et->getFullName(),
            'CLIENT_ID'          => (int)$data['clientId'],
            'CLIENT_NAME'        => $cFullName,
            'CONSUMPTION_ID'     => (int)$data['consumptionId'],
            'CONSUMPTION_NUMBER' => (string)$data['consumptionNumber'],
            'AGREEMENT_NUMBER'   => (string)$data['agreementNumber'],
            'DATE'               => (string)$data['date'],
            'DIFFERENCES'        => $differences,
        ];

        // If any template variable is empty, do not send notifications
        foreach ($templateData as $key => $tempData) {
            if (empty($tempData)) {
                throw new \Exception("Template Data ({$key}) is empty!", 500);
            }
        }

        $emailFrom = NotificationHelper::getResellerEmailFrom();
        $emails    = NotificationHelper::getEmailsByPermit($resellerId, 'tsGoodsReturn');
        if ( ! empty($emailFrom) && count($emails) > 0) {
            foreach ($emails as $email) {
                $this->sendEmployeeNotification($emailFrom, $email, $templateData, $resellerId);
                $result['notificationEmployeeByEmail'] = true;
            }
        }

        if ($notificationType === self::TYPE_CHANGE && ! empty($data['differences']['to'])) {
            $this->sendClientNotification(
                $emailFrom,
                $client->email,
                $templateData,
                $resellerId,
                $data['differences']['to']
            );
            $result['notificationClientByEmail'] = true;

            $this->sendClientSmsNotification($client->mobile, $resellerId, $templateData, $error);
            if (empty($error)) {
                $result['notificationClientBySms']['isSent'] = true;
            } else {
                $result['notificationClientBySms']['message'] = $error;
            }
        }

        return $result;
    }

    /**
     * Sends notification to employee via email.
     *
     * @param  string  $emailFrom  The email address of the sender.
     * @param  string  $emailTo  The email address of the recipient.
     * @param  array  $templateData  The data for the email template.
     * @param  int  $resellerId  The ID of the reseller.
     *
     * @return void
     */
    private function sendEmployeeNotification(
        string $emailFrom,
        string $emailTo,
        array $templateData,
        int $resellerId
    ): void {
        MessagesClient::sendMessage([
            0 => [ // MessageTypes::EMAIL
                'emailFrom' => $emailFrom,
                'emailTo'   => $emailTo,
                'subject'   => __('complaintEmployeeEmailSubject', $templateData, $resellerId),
                'message'   => __('complaintEmployeeEmailBody', $templateData, $resellerId),
            ],
        ], $resellerId, NotificationEvents::CHANGE_RETURN_STATUS);
    }

    /**
     * Sends notification to client via email and SMS.
     *
     * @param  string  $emailFrom  The email address of the sender.
     * @param  string  $clientEmail  The email address of the client.
     * @param  array  $templateData  The data for the email template.
     * @param  int  $resellerId  The ID of the reseller.
     * @param  int  $statusChanged  The changed status.
     *
     * @return void
     */
    private function sendClientNotification(
        string $emailFrom,
        string $clientEmail,
        array $templateData,
        int $resellerId,
        int $statusChanged
    ): void {
        MessagesClient::sendMessage([
            0 => [ // MessageTypes::EMAIL
                'emailFrom' => $emailFrom,
                'emailTo'   => $clientEmail,
                'subject'   => __('complaintClientEmailSubject', $templateData, $resellerId),
                'message'   => __('complaintClientEmailBody', $templateData, $resellerId),
            ],
        ], $resellerId, $client->id, NotificationEvents::CHANGE_RETURN_STATUS, $statusChanged);
    }

    /**
     * Sends SMS notification to client.
     *
     * @param  string  $clientMobile  The mobile number of the client.
     * @param  int  $resellerId  The ID of the reseller.
     * @param  array  $templateData  The data for the SMS template.
     * @param  string  $error  The error message, if any.
     *
     * @return void
     */
    private function sendClientSmsNotification(
        string $clientMobile,
        int $resellerId,
        array $templateData,
        string &$error
    ): void {
        $res = NotificationManager::send(
            $resellerId,
            $client->id,
            NotificationEvents::CHANGE_RETURN_STATUS,
            $statusChanged,
            $templateData,
            $error
        );
    }
}
