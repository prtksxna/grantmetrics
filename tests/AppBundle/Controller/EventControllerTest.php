<?php
/**
 * This file contains only the EventControllerTest class.
 */

namespace Tests\AppBundle\Controller;

use AppBundle\DataFixtures\ORM\LoadFixtures;
use DateTime;

/**
 * Integration/functional tests for the EventController.
 */
class EventControllerTest extends DatabaseAwareWebTestCase
{
    public function setup()
    {
        parent::setUp();

        $this->addFixture(new LoadFixtures());
        $this->executeFixtures();

        // Create identity mock of MusikAnimal and put it in the session.
        $identityMock = (object)['username' => 'MusikAnimal'];
        $this->container->get('session')->set('logged_in_user', $identityMock);
    }

    /**
     * No index of events without program in URL, just redirects to programs page.
     */
    public function testIndex()
    {
        $this->crawler = $this->client->request('GET', '/events');
        $this->response = $this->client->getResponse();
        $this->assertEquals(302, $this->response->getStatusCode());
    }

    public function testWorkflow()
    {
        $this->indexSpec();
        $this->newSpec();
        $this->createSpec();
        $this->updateSpec();
        $this->deleteSpec();
    }

    /**
     * Index of events for the program.
     */
    private function indexSpec()
    {
        // 'My_fun_program' was already created via fixtures.
        $this->crawler = $this->client->request('GET', '/programs/My_fun_program');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
    }

    /**
     * Form to create a new event.
     */
    private function newSpec()
    {
        $this->crawler = $this->client->request('GET', '/programs/My_fun_program/new');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());
        $this->assertContains(
            'Create a new event',
            $this->crawler->filter('.page-header')->text()
        );
    }

    /**
     * Creating a new event.
     */
    private function createSpec()
    {
        $form = $this->crawler->selectButton('Submit')->form();
        $form['form[title]'] = ' The Lion King ';
        $form['form[wikis][0]'] = 'enwiki';
        $form['form[enableTime]']->tick();
        $form['form[start][date]'] = '2017-01-01 18:00';
        $form['form[start][time]'] = '18:00';
        $form['form[end][date]'] = '2017-02-01';
        $form['form[end][time]'] = '21:00';
        $form['form[timezone]'] = 'America/New_York';
        $this->crawler = $this->client->submit($form);

        $this->response = $this->client->getResponse();
        $this->assertEquals(302, $this->response->getStatusCode());

        $event = $this->entityManager
            ->getRepository('Model:Event')
            ->findOneBy(['title' => 'The_Lion_King']);
        $this->assertNotNull($event);
        $this->assertEquals(
            'My_fun_program',
            $event->getProgram()->getTitle()
        );
        $this->assertEquals(
            new DateTime('2017-01-01 18:00'),
            $event->getStart()
        );

        $eventWiki = $this->entityManager
            ->getRepository('Model:EventWiki')
            ->findOneBy(['event' => $event]);
        $this->assertNotNull($eventWiki);
        $this->assertEquals(
            'enwiki',
            $eventWiki->getDbName()
        );
    }

    /**
     * Updating an event.
     */
    private function updateSpec()
    {
        $this->crawler = $this->client->request('GET', '/programs/My_fun_program/edit/The_Lion_King');
        $this->assertEquals(200, $this->client->getResponse()->getStatusCode());

        $form = $this->crawler->selectButton('Submit')->form();

        // FIXME: assert enableTime is set, and the event wiki

        $form['form[title]'] = 'Pinocchio';
        $form['form[wikis][0]'] = 'dewiki';
        $form['form[enableTime]']->untick();
        $this->crawler = $this->client->submit($form);

        $event = $this->entityManager
            ->getRepository('Model:Event')
            ->findOneBy(['title' => 'The_Lion_King']);
        $this->assertNull($event);
        $eventWiki = $this->entityManager
            ->getRepository('Model:EventWiki')
            ->findOneBy(['dbName' => 'enwiki']);
        $this->assertNull($eventWiki);

        $event = $this->entityManager
            ->getRepository('Model:Event')
            ->findOneBy(['title' => 'Pinocchio']);
        $this->entityManager->refresh($event);
        $this->assertNotNull($event);
        $this->assertNull($event->getStart());
        $this->assertNull($event->getEnd());
        $eventWiki = $this->entityManager
            ->getRepository('Model:EventWiki')
            ->findOneBy(['event' => $event]);
        $this->assertNotNull($eventWiki);
        $this->assertEquals(
            'dewiki',
            $eventWiki->getDbName()
        );
    }

    /**
     * Test event deletion.
     */
    private function deleteSpec()
    {
        $this->assertCount(
            1,
            $this->entityManager->getRepository('Model:Event')->findAll()
        );

        $this->crawler = $this->client->request('GET', '/programs/My_fun_program/delete/Pinocchio');
        $this->response = $this->client->getResponse();
        $this->assertEquals(302, $this->response->getStatusCode());

        $this->assertCount(
            0,
            $this->entityManager->getRepository('Model:Event')->findAll()
        );
    }
}
