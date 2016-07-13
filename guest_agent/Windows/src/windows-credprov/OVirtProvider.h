
#pragma once

#include <credentialprovider.h>

#include "CredentialsChannel.h"

class OVirtCredentials;

class OVirtProvider : public ICredentialProvider, public CredChannelListener
{
	public:
		
			// IUnknown

		IFACEMETHODIMP_(ULONG) AddRef();
		IFACEMETHODIMP_(ULONG) Release();
		IFACEMETHODIMP QueryInterface(__in REFIID riid, __deref_out void** ppv);

			// ICredentialProvider

		HRESULT STDMETHODCALLTYPE SetUsageScenario(CREDENTIAL_PROVIDER_USAGE_SCENARIO cpus, DWORD dwFlags);
		HRESULT STDMETHODCALLTYPE SetSerialization(const CREDENTIAL_PROVIDER_CREDENTIAL_SERIALIZATION *pcpcs);		
		HRESULT STDMETHODCALLTYPE Advise(ICredentialProviderEvents *pcpe, UINT_PTR upAdviseContext);
		HRESULT STDMETHODCALLTYPE UnAdvise();
		HRESULT STDMETHODCALLTYPE GetFieldDescriptorCount(DWORD *pdwCount);
		HRESULT STDMETHODCALLTYPE GetFieldDescriptorAt(DWORD dwIndex, CREDENTIAL_PROVIDER_FIELD_DESCRIPTOR **ppcpfd);
		HRESULT STDMETHODCALLTYPE GetCredentialCount(DWORD *pdwCount, DWORD *pdwDefault, BOOL *pbAutoLogonWithDefault);
		HRESULT STDMETHODCALLTYPE GetCredentialAt(DWORD dwIndex, ICredentialProviderCredential **ppcpc);

			// CredChannelListener

		virtual void OnCredentialsArrivial(LPCWSTR wzUserName, LPCWSTR wzPassword, LPCWSTR wzDomain);

		friend HRESULT OVirtCredProv_CreateInstance(REFIID riid, __deref_out void** ppv);

	protected:
		OVirtProvider();
		~OVirtProvider();

	private:
		LONG _cRef;
		CredentialsChannel *_pCredentialsChannel;
		OVirtCredentials *_pOVirtCredentials;
		ICredentialProviderEvents *_pCredentialProviderEvents;
		UINT_PTR _upAdviseContext;
};
