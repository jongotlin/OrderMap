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

class ImportGeneralCommand extends ContainerAwareCommand
{
    protected function configure()
    {
        $this
            ->setName('import:general')
            ->setDescription('')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output)
    {
        $output->writeln('Importing from general json file');

        $em = $this->getContainer()->get('doctrine')->getManager();

        $fromOrderNumber = 0;
        $order = $em->getRepository('JGIAppBundle:Order')->getNewest();
        if (false !== $order) {
            $fromOrderNumber = $order->getOrderNumber() + 1;
        }

        $request = new Request();
        $request->fromUrl(sprintf('http://'.$this->getContainer()->getParameter('general_import.url'), $fromOrderNumber));
        $response = new Response();
        if ($this->getContainer()->getParameter('general_import.username')) {
            $request->addHeader('Authorization: Basic '.base64_encode(
                sprintf(
                    '%s:%s',
                    $this->getContainer()->getParameter('general_import.username'),
                    $this->getContainer()->getParameter('general_import.password')
                )
            ));
        }

        $client = new FileGetContents();
        $client->send($request, $response);

        $rows = json_decode($response->getContent(), true);
        if (!is_null($rows)) {
            foreach ($rows as $row) {
                $order = new Order();
                $order->setStreet($row['street']);
                $order->setZip(filter_var($row['zip'], FILTER_SANITIZE_NUMBER_INT));
                $order->setCity($row['city']);
                $order->setOrderNumber($row['order_number']);
                $order->setOrderDate(\DateTime::createFromFormat('Y-m-d H:i:s', $row['order_date']));
                $em->persist($order);
            }
            $em->flush();
        }
        $output->writeln('Finished!');
    }
} 