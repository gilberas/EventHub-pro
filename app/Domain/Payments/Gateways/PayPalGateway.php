<?php

declare(strict_types=1);

namespace App\Domain\Payments\Gateways;

use App\Domain\Payments\Contracts\PaymentGateway;
use App\Domain\Payments\DTOs\PaymentResult;
use PayPal\Api\Amount as PayPalAmount;
use PayPal\Api\Payer;
use PayPal\Api\Payment;
use PayPal\Api\PaymentExecution;
use PayPal\Api\RedirectUrls;
use PayPal\Api\RefundRequest;
use PayPal\Api\Sale;
use PayPal\Api\Transaction;
use PayPal\Auth\OAuthTokenCredential;
use PayPal\Rest\ApiContext;

class PayPalGateway implements PaymentGateway
{
    private readonly ApiContext $apiContext;

    public function __construct()
    {
        $clientId = (string) config('services.paypal.client_id');
        $clientSecret = (string) config('services.paypal.secret');

        $this->apiContext = new ApiContext(
            new OAuthTokenCredential($clientId, $clientSecret),
        );

        $sandbox = (bool) config('services.paypal.sandbox', true);
        $this->apiContext->setConfig([
            'mode' => $sandbox ? 'sandbox' : 'live',
            'log.LogEnabled' => false,
        ]);
    }

    public function name(): string
    {
        return 'paypal';
    }

    public function charge(array $params): PaymentResult
    {
        try {
            $amount = new PayPalAmount;
            $amount->setTotal(number_format((float) $params['amount'], 2, '.', ''))
                ->setCurrency(strtoupper($params['currency'] ?? 'USD'));

            $transaction = new Transaction;
            $transaction->setAmount($amount)
                ->setDescription($params['description'] ?? sprintf('Booking %s', $params['booking_reference'] ?? ''))
                ->setInvoiceNumber($params['booking_reference'] ?? uniqid('inv-', true));

            $redirectUrls = new RedirectUrls;
            $redirectUrls->setReturnUrl($params['return_url'] ?? url('/checkout/success'))
                ->setCancelUrl($params['cancel_url'] ?? url('/checkout/cancel'));

            $payer = new Payer;
            $payer->setPaymentMethod('paypal');

            $payment = new Payment;
            $payment->setIntent('sale')
                ->setPayer($payer)
                ->setTransactions([$transaction])
                ->setRedirectUrls($redirectUrls);

            $createdPayment = $payment->create($this->apiContext);

            if (isset($params['payment_id']) && isset($params['payer_id'])) {
                $execution = new PaymentExecution;
                $execution->setPayerId($params['payer_id']);

                $result = $payment->execute($execution, $this->apiContext);

                return new PaymentResult(
                    success: $result->getState() === 'approved',
                    transactionId: $result->getId(),
                    amountCharged: (float) $params['amount'],
                    currency: $params['currency'] ?? 'USD',
                    rawResponse: json_decode($result->toJSON(), true),
                );
            }

            $approvalUrl = $createdPayment->getApprovalLink();

            return new PaymentResult(
                success: true,
                transactionId: $createdPayment->getId(),
                amountCharged: (float) $params['amount'],
                currency: $params['currency'] ?? 'USD',
                rawResponse: [
                    'id' => $createdPayment->getId(),
                    'state' => $createdPayment->getState(),
                    'approval_url' => $approvalUrl,
                ],
            );
        } catch (\Throwable $e) {
            return new PaymentResult(
                success: false,
                errorMessage: $e->getMessage(),
            );
        }
    }

    public function refund(string $transactionId, ?float $amount = null): PaymentResult
    {
        try {
            $sale = Sale::get($transactionId, $this->apiContext);

            $refundRequest = new RefundRequest;
            if ($amount !== null) {
                $paypalAmount = new PayPalAmount;
                $paypalAmount->setTotal(number_format($amount, 2, '.', ''))
                    ->setCurrency('USD');
                $refundRequest->setAmount($paypalAmount);
            }

            $refund = $sale->refund($refundRequest, $this->apiContext);

            return new PaymentResult(
                success: $refund->getState() === 'completed',
                transactionId: $refund->getId(),
                amountRefunded: $amount,
                rawResponse: json_decode($refund->toJSON(), true),
            );
        } catch (\Throwable $e) {
            return new PaymentResult(
                success: false,
                errorMessage: $e->getMessage(),
            );
        }
    }

    public function verify(string $transactionId): PaymentResult
    {
        try {
            $payment = Payment::get($transactionId, $this->apiContext);

            $amount = 0.0;
            $currency = 'USD';
            $transactions = $payment->getTransactions();
            if (! empty($transactions)) {
                $paypalAmount = $transactions[0]->getAmount();
                if ($paypalAmount !== null) {
                    $amount = (float) $paypalAmount->getTotal();
                    $currency = $paypalAmount->getCurrency();
                }
            }

            $state = $payment->getState();

            return new PaymentResult(
                success: $state === 'approved',
                transactionId: $payment->getId(),
                amountCharged: $amount,
                currency: $currency,
                rawResponse: json_decode($payment->toJSON(), true),
            );
        } catch (\Throwable $e) {
            return new PaymentResult(
                success: false,
                errorMessage: $e->getMessage(),
            );
        }
    }
}
