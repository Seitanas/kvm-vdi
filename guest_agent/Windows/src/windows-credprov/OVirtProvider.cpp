
#include "Pch.h"

#include "OVirtCredProv.h"
#include "OVirtProvider.h"
#include "OVirtCredentials.h"
#include "Helpers.h"

OVirtProvider::OVirtProvider() :
	_cRef(1),
	_pCredentialsChannel(NULL),
	_pOVirtCredentials(NULL),
	_pCredentialProviderEvents(NULL)
{
	DllAddRef();
}

OVirtProvider::~OVirtProvider()
{
	if (_pCredentialsChannel != NULL)
	{
		delete _pCredentialsChannel;
		_pCredentialsChannel = NULL;
	}

	if (_pOVirtCredentials != NULL)
	{
		_pOVirtCredentials->Release();
		_pOVirtCredentials = NULL;
	}

	if (_pCredentialProviderEvents != NULL)
	{
		_pCredentialProviderEvents->Release();
		_pCredentialProviderEvents = NULL;
	}

	DllRelease();
}

	// IUnknown

IFACEMETHODIMP_(ULONG) OVirtProvider::AddRef()
{
	return ++_cRef;
}

IFACEMETHODIMP_(ULONG) OVirtProvider::Release()
{
	LONG cRef = --_cRef;
	if (!cRef)
	{
		delete this;
	}
	return cRef;
}

IFACEMETHODIMP OVirtProvider::QueryInterface(__in REFIID riid, __deref_out void** ppv)
{
	HRESULT hr;
	if (ppv)
	{
		if ((IID_IUnknown == riid) || (IID_ICredentialProvider == riid))
		{
			*ppv = static_cast<IUnknown*>(this);
			reinterpret_cast<IUnknown*>(*ppv)->AddRef();
			hr = S_OK;
		}
		else
		{
			*ppv = NULL;
			hr = E_NOINTERFACE;
		}
	}
	else
	{
		hr = E_INVALIDARG;
	}
	return hr;
}

	// ICredentialProvider

HRESULT STDMETHODCALLTYPE OVirtProvider::SetUsageScenario(CREDENTIAL_PROVIDER_USAGE_SCENARIO cpus,
														 DWORD dwFlags)
{
	UNREFERENCED_PARAMETER(dwFlags);

	HRESULT hr = E_INVALIDARG;
	
	switch (cpus)
	{
		case CPUS_LOGON:
		case CPUS_UNLOCK_WORKSTATION:
		{
			if ((_pOVirtCredentials == NULL) && (_pCredentialsChannel == NULL))
			{
				_pOVirtCredentials = new OVirtCredentials();
				if (_pOVirtCredentials != NULL)
				{
					_pCredentialsChannel = new CredentialsChannel();
					if (_pCredentialsChannel == NULL)
					{
						_pOVirtCredentials->Release();
						_pOVirtCredentials = NULL;
					
						hr = E_OUTOFMEMORY;
					}
				}
				else
				{
					hr = E_OUTOFMEMORY;
				}
			}

			if (_pOVirtCredentials != NULL)
			{
				_pOVirtCredentials->SetUsageScenario(cpus);
			}

			if (_pCredentialsChannel != NULL)
			{
				hr = _pCredentialsChannel->CreateChannel(this);
			}			

			break;
		}

		case CPUS_CHANGE_PASSWORD:
		case CPUS_CREDUI:
			hr = E_NOTIMPL;
			break;

		default:
			hr = E_INVALIDARG;
			break;
	}

    return hr;
}

HRESULT STDMETHODCALLTYPE OVirtProvider::SetSerialization(const CREDENTIAL_PROVIDER_CREDENTIAL_SERIALIZATION *pcpcs)
{
	UNREFERENCED_PARAMETER(pcpcs);
	return E_NOTIMPL;
}

HRESULT STDMETHODCALLTYPE OVirtProvider::Advise(ICredentialProviderEvents *pcpe,
											   UINT_PTR upAdviseContext)
{
	if (_pCredentialProviderEvents != NULL)
	{
		_pCredentialProviderEvents->Release();
	}

	_pCredentialProviderEvents = pcpe;
	_pCredentialProviderEvents->AddRef();
	_upAdviseContext = upAdviseContext;

	return S_OK;
}

HRESULT STDMETHODCALLTYPE OVirtProvider::UnAdvise()
{
	if (_pCredentialProviderEvents != NULL)
	{
		_pCredentialProviderEvents->Release();
		_pCredentialProviderEvents = NULL;
	}

	return S_OK;
}

HRESULT STDMETHODCALLTYPE OVirtProvider::GetFieldDescriptorCount(DWORD *pdwCount)
{
	ASSERT(pdwCount != NULL);
	*pdwCount = 0;
	return S_OK;
}

HRESULT STDMETHODCALLTYPE OVirtProvider::GetFieldDescriptorAt(DWORD dwIndex,
															 CREDENTIAL_PROVIDER_FIELD_DESCRIPTOR **ppcpfd)
{
	UNREFERENCED_PARAMETER(dwIndex);
	UNREFERENCED_PARAMETER(ppcpfd);
	return E_NOTIMPL;
}

HRESULT STDMETHODCALLTYPE OVirtProvider::GetCredentialCount(DWORD *pdwCount,
														   DWORD *pdwDefault,
														   BOOL *pbAutoLogonWithDefault)
{
	ASSERT(pdwCount != NULL);
	ASSERT(pdwDefault != NULL);
	ASSERT(pbAutoLogonWithDefault != NULL);

	*pdwCount = (_pOVirtCredentials->GotCredentials() ? 1 : 0);
	*pdwDefault = 0;
	*pbAutoLogonWithDefault = TRUE;

	return S_OK;
}

HRESULT STDMETHODCALLTYPE OVirtProvider::GetCredentialAt(DWORD dwIndex,
														ICredentialProviderCredential **ppcpc)
{
	ASSERT(dwIndex < 1);
	ASSERT(ppcpc != NULL);
	ASSERT(_pOVirtCredentials != NULL);

	HRESULT hr;

	if ((dwIndex < 1) && _pOVirtCredentials && ppcpc)
	{
		hr = _pOVirtCredentials->QueryInterface(
			IID_ICredentialProviderCredential, reinterpret_cast<void**>(ppcpc));
	}
	else
	{
		hr = E_INVALIDARG;
	}

	return hr;
}

void OVirtProvider::OnCredentialsArrivial(LPCWSTR wzUserName, LPCWSTR wzPassword, LPCWSTR wzDomain)
{
	ASSERT(_pCredentialProviderEvents != NULL);
	ASSERT(_pOVirtCredentials != NULL);

	_pOVirtCredentials->SetCredentials(wzUserName, wzPassword, wzDomain);

	if (_pCredentialProviderEvents != NULL)
	{
		_pCredentialProviderEvents->CredentialsChanged(_upAdviseContext);
	}
}

HRESULT OVirtCredProv_CreateInstance(REFIID riid, void** ppv)
{
	HRESULT hr;

	OVirtProvider *pProvider = new OVirtProvider();
	if (pProvider != NULL)
	{
		hr = pProvider->QueryInterface(riid, ppv);
		pProvider->Release();
	}
	else
	{
		hr = E_OUTOFMEMORY;
	}

	return hr;
}
