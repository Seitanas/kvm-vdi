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
        logger.debug("spiceChannel redirector started for SPICE address: %s:%s", self.target_ip, self.target_port)
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
        logger.debug("spiceChannel redirector exit for SPICE address: " + self.target_ip + ":" + str(self.target_port))
        self.__spiceClient.close()
        spiceServer.close()


