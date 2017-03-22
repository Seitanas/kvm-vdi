#!/usr/bin/env python
import socket
import select
import sys
import threading
import logging

redirectorExit = False

class spiceChannel(threading.Thread):
    def __init__(self, clientSocket, target_ip, target_port):
        threading.Thread.__init__(self)
        self.__spiceClient = clientSocket
        self.target_ip = target_ip
        self.target_port = target_port
    def run(self):
        global redirectorExit
        logger = logging.getLogger('kvm-vdi-broker')
        logger.debug("spiceChannel redirector started for hypervisor: %s:%s", self.target_ip, self.target_port)
        self.__spiceClient.setblocking(0)
        dataToServer=''
        dataToClient=''
        endProcess=False
        spiceServer  = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        spiceServer.connect((self.target_ip, int(self.target_port)))
        spiceServer.setblocking(0)
        while not endProcess and not redirectorExit:
            inputs = [self.__spiceClient, spiceServer]
            outputs = []
            if len(dataToClient) > 0: #if there is data left, make socket read data
                outputs.append(self.__spiceClient)
            if len(dataToServer) > 0: #if there is data left, make socket read data
                outputs.append(spiceServer)
            readyIn, readyOut, IOerr = select.select(inputs, outputs, [], 1.0)
            for rdy in readyIn:
                if rdy == self.__spiceClient:
                    data= self.__spiceClient.recv(4096)
                    if data != None:
                        if len(data) > 0:
                            dataToServer += data
                        else:
                            endProcess = True
                if rdy == spiceServer:
                    data=spiceServer.recv(4096)
                    if data != None:
                        if len(data) > 0:
                            dataToClient += data
                        else:
                            endProcess = True
            for rdyO in readyOut:
                if rdyO == self.__spiceClient and len(dataToClient) > 0:
                    reply = self.__spiceClient.send(dataToClient)
                    if reply > 0:
                        dataToClient = dataToClient[reply:] #remove sent bytes from string
                if rdyO == spiceServer and len(dataToServer) > 0:
                    reply = spiceServer.send(dataToServer)
                    if reply > 0:
                        dataToServer = dataToServer[reply:] #remove sent bytes from string
        logger.debug("spiceChannel redirector exit for hypervisor: " + self.target_ip + ":" + str(self.target_port))
        self.__spiceClient.close()
        spiceServer.close()

class createChannel(threading.Thread):
    def __init__(self, target_ip, target_port, bind_port):
        super(createChannel, self).__init__()
        self._stop = threading.Event()
        self.target_ip = target_ip
        self.target_port = target_port
        self.bind_port = bind_port
    def stop(self):
        self._stop.set()
    def stopped(self):
        return self._stop.isSet()
    def run(self):
        global redirectorExit
        logger = logging.getLogger('kvm-vdi-broker')
        logger.debug("spiceChannel listener started on port %s", self.bind_port)
        Client = socket.socket(socket.AF_INET, socket.SOCK_STREAM)
        Client.bind(('0.0.0.0', self.bind_port))
        Client.listen(5)
        while True:
            try:
                spiceClient, addr = Client.accept()
                logger.debug("Client connected on port %s", self.bind_port)
            except KeyboardInterrupt:
                redirectorExit = True
                break
            spiceChannel(spiceClient, self.target_ip, self.target_port).start() #we need to thread client sockets, because SPICE client opens more than one stream to server.
        logger.debug("spiceChannel listener exit on port %s", self.bind_port)
        Client.close()


