<?php

namespace NW\WebService\References\Operations\Notification;

/**
 * Represents a contractor entity.
 */
class Contractor
{
    const TYPE_CUSTOMER = 0;

    public $id;
    public $type;
    public $name;
    public $seller;
    public $mobile;

    /**
     * Retrieves a contractor by ID.
     *
     * @param  int  $contractorId  The ID of the contractor to retrieve.
     *
     * @return Contractor The retrieved contractor object.
     */
    public static function getById(int $contractorId): self
    {
        // Logic to fetch contractor by ID
        return new self(); // Placeholder logic for demonstration
    }

    /**
     * Returns the full name of the contractor.
     *
     * @return string The full name of the contractor.
     */
    public function getFullName(): string
    {
        return $this->name; // Assuming name represents the full name
    }
}

/**
 * Represents a seller entity, inheriting from Contractor.
 */
class Seller extends Contractor
{
}

/**
 * Represents an employee entity, inheriting from Contractor.
 */
class Employee extends Contractor
{
}

/**
 * Represents different status types.
 */
class Status
{
    /**
     * Retrieves the name of a status based on its ID.
     *
     * @param  int  $id  The ID of the status.
     *
     * @return string The name of the status.
     */
    public static function getName(int $id): string
    {
        $statuses = [
            0 => 'Completed',
            1 => 'Pending',
            2 => 'Rejected',
        ];

        return $statuses[$id] ?? 'Unknown'; // Return 'Unknown' if status ID not found
    }
}

/**
 * Abstract class for reference operations.
 */
abstract class ReferencesOperation
{
    /**
     * Performs the operation and returns the result.
     *
     * @return array The result of the operation.
     */
    abstract public function doOperation(): array;

    /**
     * Retrieves a parameter from the request.
     *
     * @param  string  $paramName  The name of the parameter to retrieve.
     *
     * @return mixed The value of the parameter.
     */
    public function getRequest(string $paramName)
    {
        return $_REQUEST[$paramName] ?? null; // Return null if parameter not found
    }
}

/**
 * Class containing notification event constants.
 */
class NotificationHelper
{
    /**
     * Retrieves the email address of the reseller.
     *
     * @return string The email address of the reseller.
     */
    public static function getResellerEmailFrom(): string
    {
        return 'contractor@example.com';
    }

    /**
     * Retrieves emails based on the reseller ID and event.
     *
     * @param  int  $resellerId  The ID of the reseller.
     * @param  string  $event  The event triggering the email.
     *
     * @return array   The array of email addresses.
     */
    public static function getEmailsByPermit(int $resellerId, string $event): array
    {
        // Logic to fetch emails based on reseller ID and event
        return ['someemail@example.com', 'someemail2@example.com']; // Placeholder logic for demonstration
    }

}

class NotificationEvents
{
    public const CHANGE_RETURN_STATUS = 'changeReturnStatus';
    public const NEW_RETURN_STATUS = 'newReturnStatus';
}
