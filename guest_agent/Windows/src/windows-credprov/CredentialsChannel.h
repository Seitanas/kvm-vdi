
#ifndef _CREDENTIALS_CHANNEL_H_INCLUDED_
#define _CREDENTIALS_CHANNEL_H_INCLUDED_

#include <wtypes.h>

class CredChannelListener
{
	public:
		virtual void OnCredentialsArrivial(LPCWSTR wzUserName, LPCWSTR wzPassword, LPCWSTR wzDomain) = 0;
		virtual ~CredChannelListener() { }
};

class CredentialsChannel
{
	public:

		CredentialsChannel();
		~CredentialsChannel();

		bool CreateChannel(CredChannelListener *pListener);
		void DestroyChannel();

	private:

		static DWORD WINAPI CredentialsChannelThread(LPVOID lpParameter);
		void CredentialsChannelWait();
		void ParseCredentialsBuffer(BYTE *pCredBuf, DWORD nSize);

	private:

		HANDLE _hCredsPipe;
		HANDLE _hCredsThread;
		CredChannelListener *_pListener;
};

#endif // _CREDENTIALS_CHANNEL_H_INCLUDED_
