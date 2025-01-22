<?php

namespace App\Services;

/**
 * Class SlackService
 * Parses & formats Slack messages for forwarding to Telegram.
 */
class SlackService
{
    /**
     * @var string Slack channel ID to filter. If you want to accept from multiple channels,
     *             you can make this an array or remove this check altogether.
     */
    private string $targetChannelId = 'C089Z452UAD';

    /**
     * Parse a Slack event payload and return a formatted string if it's a relevant Grafana alert.
     * Otherwise, return null.
     *
     * @param array $slackEvent Slack event data (decoded JSON).
     *
     * @return string|null The formatted message for Telegram or null if not relevant.
     */
    public function parseSlackMessage(array $slackEvent): ?string
    {
        // Slack event structure normally has: type, event, etc.
        if (!isset($slackEvent['event'])) {
            return null;
        }

        $eventData   = $slackEvent['event'];
        $channelId   = $eventData['channel']     ?? '';
        $messageText = $eventData['text']        ?? '';
        $attachments = $eventData['attachments'] ?? []; // If Grafana used Slack attachments

        // 1) Ensure it's from the correct channel
        if ($channelId !== $this->targetChannelId) {
            return null;
        }

        // 2) Quick check: does it look like a Grafana alert?
        //    You could do a more robust check if needed (e.g., eventData['bot_id'] or check specific strings).
        if (! str_contains(strtolower($messageText), 'grafana')) {
            // Not from Grafana (based on your requirements).
            return null;
        }

        // 3) Determine if alert is "Started" or "Resolved" from Slack attachments color
        //    Grafana typically sends color="danger" (red) for triggered; color="good" (green) for resolved
        $alertStatus = null; // 'started' or 'resolved'
        if (!empty($attachments) && isset($attachments[0]['color'])) {
            $color = strtolower($attachments[0]['color']);
            if ($color === 'danger') {
                $alertStatus = 'started'; // Red
            } elseif ($color === 'good') {
                $alertStatus = 'resolved'; // Green
            }
        }

        // If for some reason you don't see color in attachments,
        // you might parse the text for "firing" vs "resolved" or other strings.

        if ($alertStatus === null) {
            // If color not found, skip or handle differently
            return null;
        }

        // 4) Extract important lines. Slack text might be multiline:
        $lines = explode("\n", $messageText);
        $lines = array_map('trim', $lines);
        $lines = array_filter($lines); // remove empty lines

        // We'll gather servers *only* if TENANT=FA Finance is found
        $tenantFound = false;
        $servers     = [];

        // If you have multiple repeated blocks:
        //   Agent CPU throttling
        //   ---
        //   TENANT = FA Finance
        //   SERVER = X CPU Over 85%
        //
        // We’ll parse each chunk. We'll do a simple approach:
        foreach ($lines as $line) {
            // Check for TENANT line
            if (stripos($line, 'tenant = fa finance') !== false) {
                $tenantFound = true;
            }
            // Check for SERVER line, e.g. "SERVER = 159.65.192.146 CPU Over 85%"
            if (stripos($line, 'server =') !== false) {
                // Remove "SERVER =" from the line
                $serverPart = preg_replace('/server\s*=\s*/i', '', $line);
                // The rest of the line might be "159.65.192.146 CPU Over 85%" or "159.65.192.146 CPU below 85%"
                $servers[] = trim($serverPart);
            }
        }

        // If the tenant "FA Finance" wasn’t found, skip
        if (!$tenantFound) {
            return null;
        }

        // 5) Build your Telegram message
        // You want:
        // - “Agent CPU Throttling Started\nServers over 85%:\nx\ny\nz”
        // - “Agent CPU Throttling Resolved\nServers below 85%:\nx\ny\nz”

        // Choose the text depending on whether started/resolved
        if ($alertStatus === 'started') {
            $title   = "Agent CPU Throttling Started";
            $subText = "Servers over 85%:";
        } else {
            $title   = "Agent CPU Throttling Resolved";
            $subText = "Servers below 85%:";
        }

        // Format the servers one per line
        $serverList = implode("\n", $servers);

        // You might also do Telegram markdown if desired:
        // e.g. "*Agent CPU Throttling Started*\n_Servers over 85%:_\n..."

        $finalMessage = $title . "\n" . $subText . "\n" . $serverList;

        return $finalMessage;
    }
}
