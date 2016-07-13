
#include "Pch.h"

#include <initguid.h>

#include "OVirtCredProv.h"

// A global DLL reference count.
static LONG g_nRefCount = 0;

extern HRESULT OVirtCredProv_CreateInstance(REFIID riid, void** ppv);

class CClassFactory : public IClassFactory
{
	public:

			// IUnknown

		STDMETHOD_(ULONG, AddRef)()
		{
			return _cRef++;
		}

		STDMETHOD_(ULONG, Release)()
		{
			LONG cRef = _cRef--;
			if (!cRef)
			{
				delete this;
			}
			return cRef;
		}

		STDMETHOD (QueryInterface)(REFIID riid, void** ppv) 
		{
			HRESULT hr;
			if (ppv != NULL)
			{
				if (IID_IClassFactory == riid || IID_IUnknown == riid)
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

			// IClassFactory

		STDMETHOD (CreateInstance)(IUnknown* pUnkOuter, REFIID riid, void** ppv)
		{
			HRESULT hr;
			if (!pUnkOuter)
			{
				hr = OVirtCredProv_CreateInstance(riid, ppv);
			}
			else
			{
				hr = CLASS_E_NOAGGREGATION;
			}
			return hr;
		}

		STDMETHOD (LockServer)(BOOL bLock)
		{
			if (bLock)
			{
				DllAddRef();
			}
			else
			{
				DllRelease();
			}
			return S_OK;
		}

	private:
		CClassFactory() : _cRef(1) { }
		~CClassFactory() { }

	private:
		LONG _cRef;

		friend HRESULT ClassFactory_CreateInstance(REFCLSID rclsid, REFIID riid, void** ppv);
};

HRESULT ClassFactory_CreateInstance(REFCLSID rclsid, REFIID riid, void** ppv)
{
	HRESULT hr;
	if (CLSID_OVirtProvider == rclsid)
	{
		CClassFactory* pcf = new CClassFactory;
		if (pcf)
		{
			hr = pcf->QueryInterface(riid, ppv);
			pcf->Release();
		}
		else
		{
			hr = E_OUTOFMEMORY;
		}
	}
	else
	{
		hr = CLASS_E_CLASSNOTAVAILABLE;
	}
	return hr;
}

BOOL WINAPI DllMain(HINSTANCE hInstance, DWORD dwReason, LPVOID pReserved)
{
	UNREFERENCED_PARAMETER(pReserved);

	switch (dwReason)
	{
		case DLL_PROCESS_ATTACH:
			DisableThreadLibraryCalls(hInstance);
			break;

		case DLL_PROCESS_DETACH:
		case DLL_THREAD_ATTACH:
		case DLL_THREAD_DETACH:
			break;
	}

	return TRUE;
}

STDAPI DllCanUnloadNow()
{
	return (g_nRefCount <= 0);
}

STDAPI DllGetClassObject(REFCLSID rclsid, REFIID riid, void** ppv)
{
	return ClassFactory_CreateInstance(rclsid, riid, ppv);
}

void DllAddRef()
{
	InterlockedIncrement(&g_nRefCount);
}

void DllRelease()
{
	InterlockedDecrement(&g_nRefCount);
}
