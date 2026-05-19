<?php

namespace App\Controller;

use App\Repository\NotificationRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Symfony\Component\Routing\Attribute\Route;

class NotificationStreamController extends AbstractController
{
    public function __construct(private readonly NotificationRepository $notifications) {}

    #[Route('/stream/notifications', name: 'app_stream_notifications')]
    public function notifications(Request $request): StreamedResponse
    {
        $this->denyAccessUnlessGranted('ROLE_USER');

        $lastId = max(0, (int) $request->query->get('lastId', 0));

        $response = new StreamedResponse(function () use ($lastId) {
            $start = time();
            $current = $lastId;

            while (true) {
                if (connection_aborted() || time() - $start > 55) {
                    break;
                }

                $items = $this->notifications->findAfter($current);

                foreach ($items as $notification) {
                    $current = $notification->getId();
                    $data = json_encode([
                        'id' => $notification->getId(),
                        'type' => $notification->getType(),
                        'payload' => $notification->getPayload(),
                    ]);
                    echo "id: {$notification->getId()}\ndata: {$data}\n\n";
                    flush();
                }

                sleep(2);
            }
        });

        $response->headers->set('Content-Type', 'text/event-stream');
        $response->headers->set('Cache-Control', 'no-cache');
        $response->headers->set('X-Accel-Buffering', 'no');

        return $response;
    }
}
