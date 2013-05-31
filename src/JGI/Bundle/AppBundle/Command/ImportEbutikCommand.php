<?php

namespace JGI\Bundle\AppBundle\Command;

use Symfony\Bundle\FrameworkBundle\Command\ContainerAwareCommand;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use JGI\Bundle\AppBundle\Entity\Order;
use Buzz\Message\Request;
use Buzz\Message\Response;
use Buzz\Client\FileGetContents;

class ImportEbutikCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('import:ebutik')
            ->setDescription('')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Importing from E-butik');
    
        $em = $this->getContainer()->get('doctrine')->getManager();

        $fromdate = \DateTime::createFromFormat('Y-m-d H:i:s', '2000-01-01 00:00:00');
        $order = $em->getRepository('JGIAppBundle:Order')->getNewest();
        if (false !== $order) {
            $fromdate = $order->getOrderDate();
        }

        $request = new Request(
            'GET',
            sprintf(
                '/api/1/get_orders/json/?from_date=%s',
                $fromdate->format('Y-m-d+H:i:s')
            ),
            sprintf(
                'http://%s',
                $this->getContainer()->getParameter('ebutik.url')
            )
        );
        $response = new Response();
        $request->addHeader('Authorization: Basic '.base64_encode(
            sprintf(
                '%s:%s',
                $this->getContainer()->getParameter('ebutik.username'),
                $this->getContainer()->getParameter('ebutik.password')
            )
        ));

        $client = new FileGetContents();
        $client->send($request, $response);

        $rows = json_decode($response->getContent(), true);
        if (!is_null($rows)) {
            foreach ($rows as $row) {
                if (is_array($row) && array_key_exists('customer', $row)) {
                    $zip = filter_var($row['customer']['delivery_zip_code'], FILTER_SANITIZE_NUMBER_INT);
                    if (in_array($row['status'], ['sent_payed', 'sent_not_payed', 'payed']) && 5 == strlen($zip)) {
                        $order = new Order();
                        $order->setStreet($row['customer']['delivery_address_line_first']);
                        $order->setZip($zip);
                        $order->setCity($row['customer']['delivery_city']);
                        $order->setOrderNumber($row['order_number']);
                        $order->setOrderDate(\DateTime::createFromFormat('Y-m-d H:i:s', $row['placed_at']));
                        $em->persist($order);
                    }
                }
            }
            $em->flush();
        }
        $output->writeln('Finished!');
    }
} 