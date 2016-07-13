
#include "Pch.h"

#include "CredentialsChannel.h"

static LPCTSTR lpszPipeName = "\\\\.\\pipe\\VDSMDPipe";
static const DWORD dwPipeBuffer = 1024;

static inline unsigned long _ntohl(unsigned long n)
{
	return ((n & 0xFF) << 24) | ((n & 0xFF00) << 8) | ((n & 0xFF0000) >> 8) | ((n & 0xFF000000) >> 24);
}

static inline BOOL SafeCloseHandle(HANDLE& hObject)
{
	BOOL bRet = TRUE;

	if (hObject != INVALID_HANDLE_VALUE)
	{
		bRet = ::CloseHandle(hObject);
		hObject = INVALID_HANDLE_VALUE;
	}

	return bRet;
}

static inline void SafeLocalFreeString(LPWSTR& sz)
{
	SecureZeroMemory(sz, wcslen(sz));
	::LocalFree(sz);
	sz = NULL;
}

CredentialsChannel::CredentialsChannel() :
	_hCredsPipe(INVALID_HANDLE_VALUE),
	_hCredsThread(INVALID_HANDLE_VALUE),
	_pListener(NULL)
{

}

CredentialsChannel::~CredentialsChannel()
{
	DestroyChannel();
}

bool CredentialsChannel::CreateChannel(CredChannelListener *pListener)
{
	ASSERT(_hCredsPipe == INVALID_HANDLE_VALUE);
	ASSERT(_hCredsThread == INVALID_HANDLE_VALUE);

	_hCredsPipe = ::CreateNamedPipe(lpszPipeName,
		PIPE_ACCESS_DUPLEX,
		PIPE_TYPE_MESSAGE | PIPE_READMODE_MESSAGE | PIPE_WAIT,
		PIPE_UNLIMITED_INSTANCES,
		dwPipeBuffer,
		dwPipeBuffer,
		NMPWAIT_WAIT_FOREVER,
		NULL);

	if (_hCredsPipe != INVALID_HANDLE_VALUE)
	{
		_hCredsThread = ::CreateThread(
			NULL, 0, CredentialsChannelThread, this, 0, NULL);

		if (_hCredsThread)
		{
			_pListener = pListener;
		}
	}

	return (_hCredsThread != INVALID_HANDLE_VALUE);
}

void CredentialsChannel::DestroyChannel()
{
	if (_hCredsThread != INVALID_HANDLE_VALUE)
	{
		VERIFY(::TerminateThread(_hCredsThread, 0L));
		SafeCloseHandle(_hCredsThread);
	}

	if (_hCredsPipe != NULL)
	{
		VERIFY(::DisconnectNamedPipe(_hCredsPipe));
		SafeCloseHandle(_hCredsPipe);
	}

	_pListener = NULL;
}

DWORD CredentialsChannel::CredentialsChannelThread(LPVOID lpParameter)
{
	CredentialsChannel *pThis = reinterpret_cast<CredentialsChannel*>(lpParameter);
	ASSERT(pThis != NULL);
	pThis->CredentialsChannelWait();
	return 0L;
}

void CredentialsChannel::CredentialsChannelWait()
{
	BOOL bConnected = ::ConnectNamedPipe(_hCredsPipe, NULL);
	if ((bConnected == TRUE) || (::GetLastError() == ERROR_PIPE_CONNECTED))
	{
		BYTE CredBuf[dwPipeBuffer];
		DWORD nRead = 0;

		BOOL bRead = ::ReadFile(_hCredsPipe, CredBuf, sizeof(CredBuf), &nRead, NULL);
		if ((bRead == TRUE) && (nRead > 0))
		{
			ParseCredentialsBuffer(CredBuf, nRead);
		}
	}

	// The application on the other side expect a reply. Just say nothing.
	::WriteFile(_hCredsPipe, NULL, 0, NULL, NULL);

	VERIFY(::DisconnectNamedPipe(_hCredsPipe));
	SafeCloseHandle(_hCredsPipe);

	// I'm about to terminate and quite sure about it.
	_hCredsThread = NULL;
}

void CredentialsChannel::ParseCredentialsBuffer(BYTE *pCredBuf, DWORD nSize)
{
	ASSERT(nSize > sizeof(int));
	int nUserLen = _ntohl(*((int *)pCredBuf));

	// Both name and password are encoded as UTF-8 strings. This mean that
	// we can threat the buffer size as string length when allocating the
	// the buffer for the UTF-16 conversion.

	LPWSTR szUserName = reinterpret_cast<WCHAR*>(
		::LocalAlloc(LMEM_FIXED|LMEM_ZEROINIT, ((nUserLen + 1) * sizeof(WCHAR))));

	if (szUserName == NULL)
	{
		return;
	}

	int nPasswordLen = nSize - 4 - nUserLen;

	LPWSTR szPassword = reinterpret_cast<WCHAR*>(
		::LocalAlloc(LMEM_FIXED|LMEM_ZEROINIT, ((nPasswordLen + 1) * sizeof(WCHAR))));

	if (szPassword == NULL)
	{
		SafeLocalFreeString(szUserName);
		return;
	}

	::MultiByteToWideChar(CP_UTF8, 0,
		reinterpret_cast<LPCSTR>(pCredBuf + 4), nUserLen,
		szUserName, nUserLen + 1);

	::MultiByteToWideChar(CP_UTF8, 0,
		reinterpret_cast<LPCSTR>(pCredBuf + 4 + nUserLen), nPasswordLen,
		szPassword, nPasswordLen + 1);

	if ((wcslen(szUserName) > 0) && (wcslen(szPassword) > 0))
	{
		LPWSTR szDomain = wcschr(szUserName, L'@');
		if (szDomain != NULL)
		{
			*szDomain = L'\0';
			szDomain += 1;
		}

		if (_pListener != NULL)
		{
			_pListener->OnCredentialsArrivial(szUserName, szPassword, szDomain);
		}
	}

	SafeLocalFreeString(szUserName);
	SafeLocalFreeString(szPassword);
}
