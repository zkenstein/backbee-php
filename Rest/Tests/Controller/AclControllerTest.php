<?php

/*
 * Copyright (c) 2011-2013 Lp digital system
 * 
 * This file is part of BackBuilder5.
 *
 * BackBuilder5 is free software: you can redistribute it and/or modify
 * it under the terms of the GNU General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 * 
 * BackBuilder5 is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 * 
 * You should have received a copy of the GNU General Public License
 * along with BackBuilder5. If not, see <http://www.gnu.org/licenses/>.
 */

namespace BackBuilder\Rest\Tests\Controller;

use Symfony\Component\HttpFoundation\Request;

use BackBuilder\Rest\Controller\AclController;
use BackBuilder\Tests\TestCase;


use BackBuilder\Security\Group,
    BackBuilder\Site\Site;

use Symfony\Component\Security\Acl\Domain\ObjectIdentity;
use Symfony\Component\Security\Acl\Domain\UserSecurityIdentity;

use BackBuilder\Security\Acl\Permission\MaskBuilder;

/**
 * Test for AclController class
 *
 * @category    BackBuilder
 * @package     BackBuilder\Security
 * @copyright   Lp digital system
 * @author      k.golovin
 * 
 * @coversDefaultClass \BackBuilder\Rest\Controller\AclController
 */
class AclControllerTest extends TestCase
{
    
    protected $user;
    protected $site;
    protected $groupEditor;
    
    protected function setUp()
    {
        $this->initAutoload();
        $bbapp = $this->getBBApp();
        $this->initDb($bbapp);
        $this->getBBApp()->setIsStarted(true);
        $this->initAcl();
        
        
        $this->site = new Site();
        $this->site->setLabel('Test Site')->setServerName('test_server');
        
        $this->groupEditor = new Group();
        $this->groupEditor->setName('groupName');
        $this->groupEditor->setSite($this->site);
        $this->groupEditor->setIdentifier('GROUP_ID');
        
        $bbapp->getEntityManager()->persist($this->site);
        $bbapp->getEntityManager()->persist($this->groupEditor);
        
        $bbapp->getEntityManager()->flush();
        
        // setup ACE for site
        $aclProvider = $this->getSecurityContext()->getACLProvider();
        $objectIdentity = ObjectIdentity::fromDomainObject($this->site);
        $acl = $aclProvider->createAcl($objectIdentity);
        
         // retrieving the security identity of the currently logged-in user
        $securityIdentity = new UserSecurityIdentity($this->groupEditor->getName(), 'BackBuilder\Security\Group');

        // grant owner access
        $acl->insertObjectAce($securityIdentity, MaskBuilder::MASK_EDIT);
        
        $aclProvider->updateAcl($acl);
    }
    
    protected function getController()
    {
        $controller = new AclController();
        $controller->setContainer($this->getBBApp()->getContainer());
        
        return $controller;
    }

    /**
     * @covers ::postObjectAceAction
     */
    public function test_postObjectAceAction()
    {
        $data = [
            'group_id' => $this->groupEditor->getName(),
            'object_class' => get_class($this->site),
            'object_id' => $this->site->getUid(),
            'mask' => MaskBuilder::MASK_VIEW
        ];
        $response = $this->getBBApp()->getController()->handle(new Request([], $data, [
            '_action' => 'postObjectAceAction',
            '_controller' =>  $this->getController()
        ], [], [], ['REQUEST_URI' => '/rest/1/test/', 'REQUEST_METHOD' => 'POST'] ));
        
        $res = json_decode($response->getContent(), true);

        $this->assertEquals(200, $response->getStatusCode());
        
        $this->assertInternalType('array', $res);
        $this->assertInternalType('int', $res['id']);
        
        $this->assertEquals($data['group_id'], $res['group_id']);
        $this->assertEquals($data['object_class'], $res['object_class']);
        $this->assertEquals($data['object_id'], $res['object_id']);
        $this->assertEquals($data['mask'], $res['mask']);
    }
    
    /**
     * @covers ::postObjectAceAction
     */
    public function test_postObjectAceAction_missingFields()
    {
         $response = $this->getBBApp()->getController()->handle(new Request([], [], [
            '_action' => 'postObjectAceAction',
            '_controller' =>  $this->getController()
        ], [], [], ['REQUEST_URI' => '/rest/1/test/', 'REQUEST_METHOD' => 'POST'] ));
        
        $res = json_decode($response->getContent(), true);
        
        $this->assertEquals(400, $response->getStatusCode());

        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('errors', $res);
        
        $this->assertArrayHasKey('group_id', $res['errors']);
        $this->assertArrayHasKey('object_class', $res['errors']);
        $this->assertArrayHasKey('object_id', $res['errors']);
        $this->assertArrayHasKey('mask', $res['errors']);
    }
    
    
    /**
     * @covers ::postClassAceAction
     */
    public function test_postClassAceAction()
    {
        $data = [
            'group_id' => $this->groupEditor->getName(),
            'object_class' => get_class($this->site),
            'mask' => MaskBuilder::MASK_VIEW
        ];
        $response = $this->getBBApp()->getController()->handle(new Request([], $data, [
            '_action' => 'postClassAceAction',
            '_controller' =>  $this->getController()
        ], [], [], ['REQUEST_URI' => '/rest/1/test/', 'REQUEST_METHOD' => 'POST'] ));
        
        $res = json_decode($response->getContent(), true);
        
        $this->assertEquals(200, $response->getStatusCode());
        
        $this->assertInternalType('array', $res);
        $this->assertInternalType('int', $res['id']);
        
        $this->assertEquals($data['group_id'], $res['group_id']);
        $this->assertEquals($data['object_class'], $res['object_class']);
        $this->assertEquals($data['mask'], $res['mask']);
    }
    
    
    
    /**
     * @covers ::postClassAceAction
     */
    public function test_postClassAceAction_missingFields()
    {
        $response = $this->getBBApp()->getController()->handle(new Request([], [], [
            '_action' => 'postClassAceAction',
            '_controller' =>  $this->getController()
        ], [], [], ['REQUEST_URI' => '/rest/1/test/', 'REQUEST_METHOD' => 'POST'] ));
        
        $res = json_decode($response->getContent(), true);
        
        $this->assertEquals(400, $response->getStatusCode());

        $this->assertInternalType('array', $res);
        $this->assertArrayHasKey('errors', $res);
        
        $this->assertArrayHasKey('group_id', $res['errors']);
        $this->assertArrayHasKey('object_class', $res['errors']);
        $this->assertArrayHasKey('mask', $res['errors']);
        
    }
    
    /**
     * @covers ::getEntryCollectionAction
     */
    public function testGetClassCollectionAction()
    {
        $response = $this->getBBApp()->getController()->handle(new Request([], [
        ], [
            '_action' => 'getClassCollectionAction',
            '_controller' =>  $this->getController()
        ], [], [], ['REQUEST_URI' => '/rest/1/test/'] ));
        
        $res = json_decode($response->getContent(), true);
        
        $this->assertInternalType('array', $res);
        $this->assertCount(1, $res);
        $this->assertEquals('BackBuilder\Site\Site', $res[0]['class_type']);
    }
    
}