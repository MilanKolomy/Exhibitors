<?php
declare(strict_types=1);

namespace App\Services;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception;

class MailService
{
    private PHPMailer $mailer;

    public function __construct()
    {
        $this->mailer = new PHPMailer(true);

        $this->mailer->isSMTP();
        $this->mailer->Host       = getenv('MAIL_HOST');
        $this->mailer->SMTPAuth   = true;
        $this->mailer->Username   = getenv('MAIL_USERNAME');
        $this->mailer->Password   = getenv('MAIL_PASSWORD');
        $this->mailer->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS; // SSL
        $this->mailer->Port       = (int) getenv('MAIL_PORT');
        $this->mailer->CharSet    = 'UTF-8';

        $this->mailer->setFrom(
            getenv('MAIL_FROM'),
            getenv('MAIL_FROM_NAME')
        );
    }

    public function sendConfirmation(array $exhibitor, array $festivals, array $pricing): bool
    {
        try {
            $this->mailer->clearAddresses();
            $this->mailer->addAddress($exhibitor['email'], $exhibitor['contact_name']);

            $locale = $exhibitor['locale'] ?? 'cs';

            $this->mailer->isHTML(true);
            $this->mailer->Subject = $locale === 'cs'
                ? 'Potvrzení registrace — Čokofest'
                : 'Registration Confirmation — Čokofest';

            $this->mailer->Body    = $this->buildHtml($exhibitor, $festivals, $pricing, $locale);
            $this->mailer->AltBody = $this->buildText($exhibitor, $festivals, $pricing, $locale);

            $this->mailer->send();
            return true;

        } catch (Exception $e) {
            error_log('MailService error: ' . $e->getMessage());
            return false;
        }
    }

    private function buildHtml(array $exhibitor, array $festivals, array $pricing, string $locale): string
    {
        $totalPrice = array_sum(array_column($festivals, 'price_total'));
        $festivalRows = '';

        foreach ($festivals as $f) {
            $festivalRows .= sprintf('
                <tr>
                    <td style="padding:8px 12px;border-bottom:1px solid #f0e8e4;">
                        <strong>%s – %s</strong><br>
                        <small style="color:#888;">%s · %s</small>
                    </td>
                    <td style="padding:8px 12px;border-bottom:1px solid #f0e8e4;">%s</td>
                    <td style="padding:8px 12px;border-bottom:1px solid #f0e8e4;">%s</td>
                    <td style="padding:8px 12px;border-bottom:1px solid #f0e8e4;text-align:right;">
                        <strong>%s Kč</strong>
                    </td>
                </tr>',
                htmlspecialchars($f['city']),
                htmlspecialchars($f['name']),
                htmlspecialchars($f['date_label']),
                strtoupper($f['type']),
                htmlspecialchars($f['space_label']),
                htmlspecialchars($f['elec_label']),
                number_format($f['price_total'], 0, ',', '.')
            );
        }

        return '
        <!DOCTYPE html>
        <html>
        <body style="font-family:Arial,sans-serif;color:#333;max-width:640px;margin:0 auto;">

            <div style="background:#743a25;padding:24px 32px;">
                <h1 style="color:#fff;margin:0;font-size:22px;">
                    🍫 ' . ($locale === 'cs' ? 'Potvrzení registrace' : 'Registration Confirmation') . '
                </h1>
            </div>

            <div style="padding:32px;">
                <p>' . ($locale === 'cs'
                    ? 'Dobrý den, <strong>' . htmlspecialchars($exhibitor['contact_name']) . '</strong>,'
                    : 'Dear <strong>' . htmlspecialchars($exhibitor['contact_name']) . '</strong>,') . '</p>

                <p>' . ($locale === 'cs'
                    ? 'Děkujeme za vaši registraci na festivaly Čokofest. Níže naleznete souhrn vaší objednávky.'
                    : 'Thank you for registering for Čokofest festivals. Please find your order summary below.') . '</p>

                <h3 style="color:#743a25;border-bottom:2px solid #f0e8e4;padding-bottom:8px;">
                    ' . ($locale === 'cs' ? 'Kontaktní údaje' : 'Contact details') . '
                </h3>
                <table style="width:100%;border-collapse:collapse;">
                    <tr>
                        <td style="padding:4px 0;color:#888;width:140px;">' . ($locale === 'cs' ? 'Firma' : 'Company') . ':</td>
                        <td style="padding:4px 0;"><strong>' . htmlspecialchars($exhibitor['company']) . '</strong></td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#888;">IČ:</td>
                        <td style="padding:4px 0;">' . htmlspecialchars($exhibitor['ico'] ?? '—') . '</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#888;">' . ($locale === 'cs' ? 'E-mail' : 'E-mail') . ':</td>
                        <td style="padding:4px 0;">' . htmlspecialchars($exhibitor['email']) . '</td>
                    </tr>
                    <tr>
                        <td style="padding:4px 0;color:#888;">' . ($locale === 'cs' ? 'Telefon' : 'Phone') . ':</td>
                        <td style="padding:4px 0;">' . htmlspecialchars($exhibitor['phone']) . '</td>
                    </tr>
                </table>

                <h3 style="color:#743a25;border-bottom:2px solid #f0e8e4;padding-bottom:8px;margin-top:24px;">
                    ' . ($locale === 'cs' ? 'Vybrané festivaly' : 'Selected festivals') . '
                </h3>
                <table style="width:100%;border-collapse:collapse;font-size:14px;">
                    <thead>
                        <tr style="background:#f9f5f3;">
                            <th style="padding:8px 12px;text-align:left;">' . ($locale === 'cs' ? 'Festival' : 'Festival') . '</th>
                            <th style="padding:8px 12px;text-align:left;">' . ($locale === 'cs' ? 'Prostor' : 'Space') . '</th>
                            <th style="padding:8px 12px;text-align:left;">' . ($locale === 'cs' ? 'Elektřina' : 'Electricity') . '</th>
                            <th style="padding:8px 12px;text-align:right;">' . ($locale === 'cs' ? 'Cena' : 'Price') . '</th>
                        </tr>
                    </thead>
                    <tbody>' . $festivalRows . '</tbody>
                    <tfoot>
                        <tr style="background:#743a25;color:#fff;">
                            <td colspan="3" style="padding:12px;font-weight:bold;">
                                ' . ($locale === 'cs' ? 'Celková cena' : 'Total price') . '
                            </td>
                            <td style="padding:12px;text-align:right;font-weight:bold;font-size:16px;">
                                ' . number_format($totalPrice, 0, ',', '.') . ' Kč
                            </td>
                        </tr>
                    </tfoot>
                </table>

                <p style="margin-top:24px;font-size:13px;color:#888;">
                    ' . ($locale === 'cs'
                        ? 'Toto je automaticky generovaný e-mail. V případě dotazů nás kontaktujte na ' . getenv('MAIL_FROM')
                        : 'This is an automatically generated e-mail. For questions contact us at ' . getenv('MAIL_FROM')) . '
                </p>
            </div>

            <div style="background:#f9f5f3;padding:16px 32px;font-size:12px;color:#aaa;text-align:center;">
                &copy; ' . date('Y') . ' Čokofest
            </div>

        </body>
        </html>';
    }

    private function buildText(array $exhibitor, array $festivals, array $pricing, string $locale): string
    {
        $totalPrice = array_sum(array_column($festivals, 'price_total'));
        $lines = [];

        $lines[] = $locale === 'cs' ? 'POTVRZENÍ REGISTRACE — ČOKOFEST' : 'REGISTRATION CONFIRMATION — ČOKOFEST';
        $lines[] = str_repeat('-', 40);
        $lines[] = '';
        $lines[] = ($locale === 'cs' ? 'Firma: ' : 'Company: ') . $exhibitor['company'];
        $lines[] = 'IČ: ' . ($exhibitor['ico'] ?? '—');
        $lines[] = 'E-mail: ' . $exhibitor['email'];
        $lines[] = ($locale === 'cs' ? 'Telefon: ' : 'Phone: ') . $exhibitor['phone'];
        $lines[] = '';
        $lines[] = $locale === 'cs' ? 'VYBRANÉ FESTIVALY:' : 'SELECTED FESTIVALS:';
        $lines[] = str_repeat('-', 40);

        foreach ($festivals as $f) {
            $lines[] = $f['city'] . ' – ' . $f['name'] . ' (' . $f['date_label'] . ')';
            $lines[] = '  ' . $f['space_label'] . ' + ' . $f['elec_label'];
            $lines[] = '  ' . number_format($f['price_total'], 0, ',', '.') . ' Kč';
        }

        $lines[] = str_repeat('-', 40);
        $lines[] = ($locale === 'cs' ? 'Celková cena: ' : 'Total price: ')
                 . number_format($totalPrice, 0, ',', '.') . ' Kč';

        return implode("\n", $lines);
    }
}