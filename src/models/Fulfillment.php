<?php
/**
 * Fulfillments plugin for Craft CMS 3.x
 *
 * Add Shopify like fulfillments to your Craft Commerce orders.
 *
 * @link      https://tas.dev
 * @copyright Copyright (c) 2019 Jayden Smith
 */

namespace tasdev\orderfulfillments\models;


use DateTime;
use craft\base\Model;
use craft\commerce\Plugin as Commerce;
use craft\commerce\elements\Order;
use tasdev\orderfulfillments\base\TrackingCarrier;
use tasdev\orderfulfillments\OrderFulfillments;
use yii\base\InvalidConfigException;


/**
 * @author    Jayden Smith
 * @package   Fulfillments
 * @since     1.0.0
 */
class Fulfillment extends Model
{
    // Public Properties
    // =========================================================================

    /**
     * @var ?int
     */
    public ?int $id = null;

    /**
     * @var int
     */
    public int $orderId;

    /**
     * @var string
     */
    public string $trackingNumber;

    /**
     * @var ?string
     */
    public ?string $trackingCarrierClass;

    /**
     * @var string
     */
    public string $uid;

    /**
     * @var ?DateTime
     * @since 1.1
     */
    public ?DateTime $dateCreated;

    /**
     * @var ?DateTime
     * @since 1.1
     */
    public ?DateTime $dateUpdated;


    // Private Properties
    // =========================================================================

    /**
     * @var ?Order
     */
    private ?Order $_order = null;

    /**
     * @var ?FulfillmentLine[]
     */
    private ?array $_fulfillmentLines = null;


    // Public Methods
    // =========================================================================

    /**
     * Gets the order.
     *
     * @return ?Order
     * @throws InvalidConfigException
     */
    public function getOrder(): ?Order
    {
        if (!$this->_order) {
            $this->_order = Commerce::getInstance()->getOrders()->getOrderById($this->orderId);
        }

        return $this->_order;
    }

    /**
     * Sets the order.
     *
     * @param Order $order
     */
    public function setOrder(Order $order)
    {
        $this->orderId = $order->id;
        $this->_order = $order;
    }

    /**
     * Gets the selected tracking carrier.
     *
     * @return ?TrackingCarrier
     */
    public function getTrackingCarrier(): ?TrackingCarrier
    {
        $class = $this->trackingCarrierClass;
        if (!$class) {
            return null;
        }

        return new $class([
            'trackingNumber' => $this->trackingNumber,
        ]);
    }

    /**
     * Gets all fulfillment lines for this fulfillment.
     *
     * @return ?FulfillmentLine[]
     */
    public function getFulfillmentLines(): ?array
    {
        $this->_fetchFulfillmentLines();
        return $this->_fulfillmentLines;
    }

    /**
     * Sets the fulfillment lines for this fulfillment.
     *
     * @param FulfillmentLine[] $fulfillmentLines
     */
    public function setFulfillmentLines(array $fulfillmentLines)
    {
        $this->_fulfillmentLines = $fulfillmentLines;
    }

    /**
     * Adds a fulfillment line to this fulfillment.
     *
     * @param FulfillmentLine $fulfillmentLine
     */
    public function addFulfillmentLine(FulfillmentLine $fulfillmentLine)
    {
        $this->_fetchFulfillmentLines();
        $this->_fulfillmentLines[] = $fulfillmentLine;
    }

    /**
     * @inheritdoc
     */
    public function rules(): array
    {
        $rules = parent::rules();

        $rules[] = ['orderId', 'required'];

        return $rules;
    }

    /**
     * @inheritDoc
     */
    public function validate($attributeNames = null, $clearErrors = true): bool
    {
        $hasErrors = false;

        if (is_array($this->_fulfillmentLines)) {
            foreach ($this->_fulfillmentLines as $fulfillmentLine) {
                $fulfillmentLine->validate();

                if ($fulfillmentLine->hasErrors()) {
                    $hasErrors = true;
                }
            }
        }

        return parent::validate($attributeNames, $clearErrors) && !$hasErrors;
    }


    // Private Methods
    // =========================================================================

    private function _fetchFulfillmentLines()
    {
        if (!$this->_fulfillmentLines) {
            $this->_fulfillmentLines = OrderFulfillments::getInstance()->getFulfillmentLines()->getFulfillmentLinesByFulfillment($this);
        }
    }
}
