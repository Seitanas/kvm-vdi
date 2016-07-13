#!/usr/bin/env python

class BytesIO:
    def __init__(self, buffer):
        self._data = buffer
        if not self._data:
            self._data = str()
        self._pos = 0

    def getvalue(self):
        return self._data

    def close(self):
        pass

    def readline(self):
        return self.read(self._data[self._pos:].find('\n') + 1)

    def read(self, n=None):
        if n == None:
            n = -1
        if not isinstance(n, (int, long)):
            raise TypeError("Argument must be an integer")
        if n < 0:
            n = len(self._data)
        if len(self._data) <= self._pos:
            return ''
        newpos = min(len(self._data), self._pos + n)
        b = self._data[self._pos : newpos]
        self._pos = newpos
        return b

    def readable(self):
        return True

    def writable(self):
        return True

    def seekable(self):
        return False
